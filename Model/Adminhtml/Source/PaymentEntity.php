<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayMbwayGateway\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

class PaymentEntity implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => "11249", 'label' => "11249 - 12101" ],
            [   'value' => "10241", 'label' => "10241 - 12029"   ]
        ];
    }
}
