<?php
namespace Hipay\HipayMbwayGateway\Block;

use Magento\Framework\Phrase;
use Magento\Framework\Registry;

class Info extends \Magento\Payment\Block\Info
{

    public function getPaymentInfoData()
    {

	$orderId = $this->getRequest()->getParam('order_id');
	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	$order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderId);

	if ( $order->getPayment()->getMethod() == "hipay_mbway_gateway"){

		$payment = $order->getPayment();	

        $details['MBWAY_Entity'] = $payment->getAdditionalInformation('MBWAY_Entity');
       	$details['MBWAY_Reference'] = $payment->getAdditionalInformation('MBWAY_Reference');
		$details['MBWAY_AmountOut'] = $payment->getAdditionalInformation('MBWAY_AmountOut');
		$details['MBWAY_CategoryId'] = $payment->getAdditionalInformation('MBWAY_CategoryId');
	
        return $details;
	}
	return;

    }

}


