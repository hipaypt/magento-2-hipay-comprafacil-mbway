<?php

class HipayMbway_Autoloader {

    public static $classArray = array(
        'MbwayClient.php',
        'MbwayRequest.php',
        'MbwayNotification.php',
        'MbwayRequestTransaction.php',
        'MbwayRequestDetails.php',
        'MbwayRequestResponse.php',
        'MbwayRequestDetailsResponse.php',
        'MbwayRequestTransactionResponse.php',
        'MbwayPaymentDetailsResult.php',
        'MbwayRequestRefund.php'
    );

    /**
     * Load classes
     */
    public static function loader() {
        foreach (HipayMbway_Autoloader::$classArray as $class) {
            require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . $class);
        }
    }

}

spl_autoload_register('HipayMbway_Autoloader::loader');
