<?php

namespace Hipay\HipayMbwayGateway\Observer;

use Magento\Framework\Event\ObserverInterface;

class SendMailOnOrderSuccess implements ObserverInterface
{
    protected $orderModel;
    protected $orderSender;
    protected $checkoutSession;
    protected $orderCommentSender;
    protected $_payment;
    protected $assetRepo;

    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderModel,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender,
        \Magento\Framework\View\Asset\Repository $assetRepo
    )
    {
        $this->orderModel = $orderModel;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
        $this->orderCommentSender = $orderCommentSender;
        $this->assetRepo = $assetRepo;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if(count($orderIds))
        {       
            //$this->checkoutSession->setForceOrderMailSentOnSuccess(true);
            $order = $this->orderModel->create()->load($orderIds[0]);
    	    $this->_payment = $order->getPayment();
    	    if ($this->_payment->getMethod() == 'hipay_mbway_gateway'){
        		$this->checkoutSession->setForceOrderMailSentOnSuccess(false);
        		if ($order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED && $order->getState() != \Magento\Sales\Model\Order::STATE_PROCESSING){

        			$order->setSendEmail(false)->setCanSendNewEmailFlag(false)->save()->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)->setStatus("pending")->save();
                	/*
                    //uncomment to send ref to client
                    $comment = $this->getReferenceTable() ;
        			$this->orderCommentSender->send($order, true, $comment);
                    */
        		} else {
                    $order->setSendEmail(false)->setCanSendNewEmailFlag(false)->save();
        			$this->orderSender->send($order, true);
        	    }
        	}
        }
    }


    protected function getReferenceTable(){

                $referenceTable = '<table cellpadding="6" cellspacing="2" style="width: 300px; height: 55px; margin: 10px 0 2px 0;border: 1px solid #ddd;background:#fff;">
                        <tr>
                                <td style="background-color: #ccc;color:#313131;text-align:center;" colspan="2">'. __('Please open your MB WAY App and authorize the transaction.') . '</td>
                        </tr>
                        <tr>
				 <td style="width:100px;background-color:#fff;" rowspan="2"><img src="' . $this->getLogoUrl() . '"/></td>
                                <td style="width:100px;background-color:#fff;">'. __('REFERENCE') . '</td>
                                <td style="font-weight:bold;width:300px;background-color:#fff;">'. $this->getMbwayReference(). '</td>
                        </tr>
                        <tr>
                                <td style="background-color:#fff;">'. __('AMOUNT'). '</td>
                                <td style="font-weight:bold;background-color:#fff;">'. $this->getMbwayAmount(). ' &euro;</td>
                        </tr>
                </table>';

                return $referenceTable;
    }

    	protected function getLogoUrl() {
        	return $this->assetRepo->getUrlWithParams('Hipay_HipayMbwayGateway::images/mbway.jpg', ['_secure' => true]);
    	}

        protected function getMbwayEntity()
        {
                return $this->_payment->getAdditionalInformation('MBWAY_Entity');
        }       

        protected function getMbwayReference()
        {
                return $this->_payment->getAdditionalInformation('MBWAY_Reference');
        }       

        protected function getMbwayAmount()
        {
                return $this->_payment->getAdditionalInformation('MBWAY_AmountOut');
        }       

        public function getMbwayCategoryId()
        {
            return $this->_payment->getAdditionalInformation('MBWAY_CategoryId');
        }     
   
        protected function getMbwayAccountType()
        {
                return $this->_payment->getAdditionalInformation('accountType');
        }       

}
