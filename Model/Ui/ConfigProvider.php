<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayMbwayGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

final class ConfigProvider implements ConfigProviderInterface {

    const CODE = 'hipay_mbway_gateway';

    protected $assetRepo;

    public function __construct(
            \Magento\Framework\View\Asset\Repository $assetRepo
    ) {
        $this->assetRepo = $assetRepo;
    }
    
    public function getConfig() {

        return [
            'payment' => [
                self::CODE => [
                    'paymentImageSrc' => $this->getLogoUrl(),
                    'transactionResults' => [
                        "code" => self::CODE,
                        "time" => date("Y-m-d H:i:s"),
                        "uniqid" => md5(uniqid() . date("YmdHis"))
                    ]
                ]
            ]
        ];
    }

    /*
     * @return string
     */
    protected function getLogoUrl() {
        return $this->assetRepo->getUrlWithParams('Hipay_HipayMbwayGateway::images/logo_checkout.png', ['_secure' => true]);
    }

}
