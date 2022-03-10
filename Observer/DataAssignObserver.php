<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayMbwayGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    public function execute(Observer $observer)
    {
		$method = $this->readMethodArgument($observer);
		$data = $this->readDataArgument($observer);
	    $paymentInfo = $this->readPaymentModelArgument($observer);
	    $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
	    $paymentInfo->setAdditionalInformation($additionalData);
		return;               
    }
}