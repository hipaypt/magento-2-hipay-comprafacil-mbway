<?php

namespace Hipay\HipayMbwayGateway\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class AddExtraDataToTransport implements ObserverInterface
{  

	protected $_order;
	protected $_payment;
    protected $assetRepo;
	protected $orderSender;

	public function __construct(\Magento\Framework\View\Asset\Repository $assetRepo, \Magento\Sales\Api\Data\OrderInterface $order, OrderSender $orderSender	) {
		$this->_order = $order;
        	$this->assetRepo = $assetRepo;
		$this->orderSender = $orderSender;
	}

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		$transport = $observer->getEvent()->getTransport();
		$incrementId = $transport['order']['increment_id'];
		$this->_order->loadByIncrementId($incrementId);
		$this->_payment = $this->_order->getPayment();
		if ( $this->_payment->getMethod() == 'hipay_mbway_gateway'){
			$this->_order->setSendEmail(false)->setCanSendNewEmailFlag(false)->save()->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)->setStatus("pending")->setCanSendNewEmailFlag(false)->save();
			$method = $this->_payment->getMethodInstance();
			$methodTitle = $method->getTitle();
			$transport['payment_html'] = $methodTitle . $this->getReferenceTable() ;
		}
    }

	protected function getReferenceTable(){
		
		$referenceTable = '<table cellpadding="6" cellspacing="2" style="width: 300px; height: 55px; margin: 10px 0 2px 0;border: 1px solid #ddd">
			<tr>
				<td style="background-color: #ccc;color:#313131;text-align:center;" colspan="2">'. __('Please open your MB WAY App and authorize the transaction.') . '</td>
			</tr>
			<tr>
				<td>'. __('REFERENCE'). '</td>
				<td style="font-weight:bold;">'. $this->getMbwayReference(). '</td>
			</tr>
			<tr>
				<td>'. __('AMOUNT'). '</td>
				<td style="font-weight:bold;">'. $this->getMbwayAmount(). ' &euro;</td>
			</tr>
		</table>';

		return $referenceTable;
	}

    protected function getLogoUrl() {
        return $this->assetRepo->getUrlWithParams('Hipay_HipayMbwayGateway::images/mbway.jpg', ['_secure' => true]);
    }  
    
	public function getMbwayEntity()
	{
		return $this->_payment->getAdditionalInformation('MBWAY_Entity');
	}	

	public function getMbwayReference()
	{
		return $this->_payment->getAdditionalInformation('MBWAY_Reference');
	}	

	public function getMbwayAmount()
	{
		return $this->_payment->getAdditionalInformation('MBWAY_AmountOut');
	}	

	public function getMbwayCategoryId()
	{
		return $this->_payment->getAdditionalInformation('MBWAY_CategoryId');
	}	
    
	public function getMbwayAccountType()
	{
		return $this->_payment->getAdditionalInformation('accountType');
	}	
}
