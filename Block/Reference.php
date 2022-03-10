<?php
namespace Hipay\HipayMbwayGateway\Block;

class Reference extends \Magento\Sales\Block\Order\Totals
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    protected $_order;
    protected $_payment;
    protected $_showTable;
    protected $_orderId;
    protected $_methodCode;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
    }

    public function getOrder()
    {

	$this->_orderId = $this->checkoutSession->getLastRealOrderId();
        $this->_order = $this->_orderFactory->create()->loadByIncrementId($this->_orderId);
        $this->_payment = $this->_order->getPayment();

        $this->_methodCode = $this->_payment->getMethod();
		$this->_showTable = false;				
        if ( $this->_methodCode == "hipay_mbway_gateway" )
        {
			$this->_showTable = true;
			$this->_payment->setIsTransactionClosed(false);
			$this->_order->setSendEmail(false)->setCanSendNewEmailFlag(false)->save()->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)->setStatus("pending")->setCanSendNewEmailFlag(false);
			$this->_order->save();		
		}
        return  $this->_order;
    }

	public function getReferenceTable(){
		$referenceTable = "";
		
		if ($this->showTable()) {

			//$referenceTable = "<p><br>" . __('Your order # is: ') .  $this->getOrderId() . ".</p>";
            		//$referenceTable .= "<p><br>" . __('You will receive an email with your order details.') . "</p>";
            		$referenceTable .= "<p><br>";

			$referenceTable .= '<div class="MBWAY_referencia"><table cellpadding="6" cellspacing="2" style="width: 350px; height: 55px; margin: 10px 0 2px 0;border: 1px solid #ddd">
				<tr>
					<td style="background-color: #ccc;color:#313131;text-align:center;" colspan="2">';
				$referenceTable .= __('Please open your MB WAY App and authorize the transaction.') . '</td>
				</tr>
				<tr>
					<td rowspan="3" style="width:110px;padding: 0px 5px 0px 5px;vertical-align: middle;"><img src="'. $this->getViewFileUrl("Hipay_HipayMbwayGateway::images/mbway.jpg"). '" style="margin-bottom: 0px; margin-right: 0px;"/></td>
					<td style="width:100px;">';
				$referenceTable .= __('REFERENCE') . '<br>
					<span style="font-weight:bold;">'. $this->getMbwayReference(). '</span></td>
				</tr>
				<tr>
					<td>';
				$referenceTable .= __('AMOUNT'). '<br>
					<span style="font-weight:bold;">'. $this->getMbwayAmount(). ' &euro;</span></td>
				</tr>
				<tr>
					<td>';
				$referenceTable .= __('PHONE NUMBER'). '<br>
					<span style="font-weight:bold;">'. $this->getMbwayPhone(). '</span></td>
				</tr>
			</table></div>';

			$referenceTable .= "<br></p>";

		}	
		return $referenceTable;	

	}

	public function showTable()
	{
	return $this->_showTable;
	}

	public function getMethodCode()
	{
	return $this->_methodCode;
	}

	public function getCustomerId()
	{
	return $this->customerSession->getCustomer()->getId();
	}

	public function getOrderId()
	{
	return $this->_orderId;
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
    
	public function getMbwayPhone()
	{
		return $this->_payment->getAdditionalInformation('MBWAY_Phone');
	}	

	public function getMbwayAccountType()
	{
		return $this->_payment->getAdditionalInformation('accountType');
	}	

}
