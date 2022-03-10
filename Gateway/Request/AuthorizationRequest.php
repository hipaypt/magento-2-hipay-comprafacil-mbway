<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayMbwayGateway\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;
    private $sandbox;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
		
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];
        $order = $payment->getOrder();
        $this->sandbox = $this->config->getValue('sandbox', $order->getStoreId() );                
        $address = $order->getShippingAddress();
        
        $phone = $payment->getPayment()->getAdditionalInformation("altPhoneNumber");
        if ($phone == "") 
        	$phone = $address->getTelephone();
        
        return array(
            'TYPE' 			=> 'REQUEST',
            'INVOICE' 			=> $order->getOrderIncrementId(),
            'AMOUNT' 			=> $order->getGrandTotalAmount(),
            'CURRENCY' 		=> $order->getCurrencyCode(),
            'EMAIL' 			=> $address->getEmail(),
            'PHONE' 			=> $phone,
            'CUSTOMER_NAME'		=> $address->getFirstName() . " " . $address->getLastName(),
            'DEBUG' 			=> $this->config->getValue('debug',     				$order->getStoreId() ),
            'SANDBOX' 			=> $this->sandbox,
            'ENTITY' 			=> $this->config->getValue('payment_entity',  	$order->getStoreId() ),
            'MERCHANT_CREDENTIALS' 	=> $this->config->getValue('api_' .	$this->getApiType(),     $order->getStoreId() )
        );

    }
    
    private function getApiType(){
		
			if ($this->sandbox)
				return "sandbox";
			else
				return "production";		
	}
    
}
