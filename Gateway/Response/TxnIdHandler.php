<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayMbwayGateway\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class TxnIdHandler implements HandlerInterface
{
    const ENTITY 			= 'ENTITY';
    const REFERENCE 		= 'REFERENCE';
    const AMOUNTOUT 		= 'AMOUNTOUT';
    const CATEGORY_ID 		= 'CATEGORY_ID';
    const ACCOUNT_TYPE 		= 'ACCOUNT_TYPE';
    const TRANSACTION_ID 	= 'TRANSACTION_ID';
    const PHONE 			= 'PHONE';

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        $payment->setTransactionId($response[self::TRANSACTION_ID]);
        $payment->setAdditionalInformation("MBWAY_Entity",$response[self::ENTITY]);
        $payment->setAdditionalInformation("MBWAY_Reference",$response[self::REFERENCE]);
        $payment->setAdditionalInformation("MBWAY_AmountOut",$response[self::AMOUNTOUT]);
        $payment->setAdditionalInformation("MBWAY_CategoryId",$response[self::CATEGORY_ID]);
        $payment->setAdditionalInformation("MBWAY_Phone",$response[self::PHONE]);
        $payment->setAdditionalInformation("accountType",$response[self::ACCOUNT_TYPE]);
	$payment->setIsTransactionPending(true);
        $payment->setIsTransactionClosed(false);
			
    }


}
