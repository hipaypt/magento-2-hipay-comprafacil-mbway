<?php
namespace Hipay\HipayMbwayGateway\Controller\Notify;

include_once(__DIR__ . '/../../lib/HipayMbway/autoload.php');

use Magento\Framework\App\Action\Action as AppAction;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;

use HipayMbway\MbwayClient;
use HipayMbway\MbwayRequestDetails;
use HipayMbway\MbwayRequestResponse;
use HipayMbway\MbwayRequestDetailsResponse;
use HipayMbway\MbwayRequestTransactionResponse;
use HipayMbway\MbwayPaymentDetailsResult;
use HipayMbway\MbwayNotification;

class Index extends AppAction
{

    protected $_messageManager;
    protected $_context;
    protected $_order;
    protected $_sandbox;
    protected $_credentials;
    protected $_payment;
    protected $_entity;
    protected $orderSender;
    protected $request;
    protected $_logger;

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
        $this->_messageManager = $context->getMessageManager();

        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof HttpRequest && $request->isPost()) {
                $request->setParam('isAjax', true);
                $request->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }    

    public function execute()
    {

		$entityBody = file_get_contents('php://input');

		$notification = new MbwayNotification($entityBody);
		if ($notification->get_isJson() === false) {
			die("Invalid notification received.");
		}

		$idformerchant = $notification->get_ClientExternalReference();
		$transactionId = $notification->get_OperationId();
		$transactionAmount = $notification->get_Amount();
		$transactionStatusCode = $notification->get_StatusCode();

		$this->orderSender = $this->_objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
		$this->_order = $this->_objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($idformerchant);
		$this->_payment = $this->_order->getPayment();

		$storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORES;
		$this->_sandbox 		= $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/hipay_mbway_gateway/sandbox',$storeScope);
		$this->_entity	 		= $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/hipay_mbway_gateway/payment_entity',$storeScope);
		if ($this->_sandbox)
			$this->_credentials		= $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/hipay_mbway_gateway/api_sandbox' ,$storeScope);
		else
			$this->_credentials		= $this->_objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/hipay_mbway_gateway/api_production',$storeScope);

		switch ($transactionStatusCode) {
			case "c1":
			print "MB WAY payment confirmed for transaction $transactionId." . PHP_EOL;

			if ($this->checkTransactionStatus($transactionId,$transactionStatusCode)){
				print "status check ok." . PHP_EOL;
			} else {
				print "status check nok." . PHP_EOL;
				exit;
			}	
			echo " AND CAPTURE!";
			if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING){
				$this->_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)->setStatus("processing");
				$comment = "Captured, " . date('Y-m-d H:i:s');
				$this->_order->addStatusHistoryComment($comment)->setIsCustomerNotified(true)->setEntityName('order');
				$this->_order->save();
					$this->orderSender->send($this->_order, $comment, true);
				$this->_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING)->setStatus("processing")->save();
			}


			break;
			case "c3":
			case "c6":
			case "vp1":
				print "Waiting capture notification for transaction $transactionId." . PHP_EOL;
			break;
			case "ap1":
				print "Refunded transaction $transactionId." . PHP_EOL;
			break;
			case "c2":
			case "c4":
			case "c5":
			case "c7":
			case "c8":
			case "c9":
			case "vp2":
				print "MB WAY payment cancelled transaction $transactionId." . PHP_EOL;
				if ($this->checkTransactionStatus($transactionId,$transactionStatusCode)){
					print "status check ok." . PHP_EOL;
				} else {
					print "status check nok." . PHP_EOL;
					exit;
				}	
				if ($this->_order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED && $this->_order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING){
					$this->_order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)->setStatus("canceled");
					$comment = "Authorization failed, " . date('Y-m-d H:i:s');
					$this->_order->addStatusHistoryComment($comment)->setIsCustomerNotified(true)->setEntityName('order');
					$this->_order->save();	
					echo "NO AUTHORIZATION!";
				}		
				break;
		}
	}

	protected function checkTransactionStatus($reference,$status){

		$mbway = new MbwayClient($this->_sandbox);
		$mbwayRequestDetails = new MbwayRequestDetails($this->_credentials["merchant_api_login"], $this->_credentials["merchant_api_password"], $reference, $this->_entity);
		$mbwayRequestDetailsResult = new MbwayRequestDetailsResponse($mbway->getPaymentDetails($mbwayRequestDetails)->GetPaymentDetailsResult);

		/*
		 * Check Operation result  
		 */

		if ($mbwayRequestDetailsResult->get_ErrorCode() <> 0 || !$mbwayRequestDetailsResult->get_Success()) {
		    return false;
		} else {

		    $detailStatusCode = $mbwayRequestDetailsResult->get_MBWayPaymentDetails()->get_StatusCode();
		    $detailAmount = $mbwayRequestDetailsResult->get_MBWayPaymentDetails()->get_Amount();
		    $detailOperationId = $mbwayRequestDetailsResult->get_MBWayPaymentDetails()->get_OperationId();

		    if ($detailStatusCode === $status) {
				return true;
		    }
		}
		return false;			
		
	}
	
	
}
	