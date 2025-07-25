<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Hipay\HipayMbwayGateway\Gateway\Http\Client;

include_once(__DIR__ . '/../../../lib/HipayMbway/autoload.php');

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Exception\LocalizedException;

use HipayMbway\MbwayClient;
use HipayMbway\MbwayRequestTransaction;
use HipayMbway\MbwayRequestTransactionResponse;


class ClientProcessor implements ClientInterface
{
	const SUCCESS = 1;
	const FAILURE = 0;

	private $sandbox;
	private $entity;
	private $ws_url;
	private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    private $logger;
	private $soapClientFactory;
	private $urlBuilder;
	
    /**
     * @param Logger $logger
     */
    public function __construct( Logger $logger, \Magento\Framework\Webapi\Soap\ClientFactory $soapClientFactory, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\UrlInterface $urlBuilder ) {
        $this->logger 			= $logger;
        $this->soapClientFactory 	= $soapClientFactory;
        $this->urlBuilder		= $urlBuilder;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {

		$obj = $transferObject->getBody();
		
		$sandbox 	= $obj["SANDBOX"];
		$entity 	= $obj["ENTITY"];
		$category 	= $obj["MERCHANT_CREDENTIALS"]["merchant_api_category"];
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$store = $objectManager->get('Magento\Framework\Locale\Resolver'); 

		$username = $obj["MERCHANT_CREDENTIALS"]["merchant_api_login"];
		$password = $obj["MERCHANT_CREDENTIALS"]["merchant_api_password"];

		/*
		 * Transaction parameters
		 */

		$notificationUrl = $this->urlBuilder->getUrl('hipay_mbway_gateway/notify/index', ['_secure' => true]) . "?order=" . $obj["INVOICE"];
		$amount = number_format($obj["AMOUNT"],2,".","");
		$customerPhone = $this->sanitizePhone($obj["PHONE"]);
		$customerEmail = $obj["EMAIL"];
		$merchantId = $obj["INVOICE"];
		$orderDescription = $obj["INVOICE"];
		$customerVATNumber = "";
		$customerName = $obj["CUSTOMER_NAME"];

		/*
		 * Create a Transaction
		 */

		$mbway = new MbwayClient($sandbox);
		$mbwayRequestTransaction = new MbwayRequestTransaction($username, $password, $amount, $customerPhone, $customerEmail, $merchantId, $category, $notificationUrl, $entity);
		$mbwayRequestTransaction->set_description($orderDescription);
		$mbwayRequestTransaction->set_clientVATNumber($customerVATNumber);
		$mbwayRequestTransaction->set_clientName($customerName);
		$mbwayRequestTransactionResult = new MbwayRequestTransactionResponse($mbway->createPayment($mbwayRequestTransaction)->CreatePaymentResult);

		
		$parameters = array();
		$parameters["merchantId"] 	= $merchantId;
		$parameters["customerEmail"] 	= $customerEmail;
		$parameters["username"] 	= $username;
		$parameters["sandbox"] 	= $sandbox;
		$parameters["amount"] 		= $amount;

		/*
		 * Check Transaction creation result
		 */

		if ($mbwayRequestTransactionResult->get_Success() && $mbwayRequestTransactionResult->get_ErrorCode() == "0") {
			//vp1
			$transactionId = $mbwayRequestTransactionResult->get_MBWayPaymentOperationResult()->get_OperationId();
		} else {

			$parameters["error"] 	= $mbwayRequestTransactionResult->get_MBWayPaymentOperationResult()->get_StatusCode();
			$parameters["errorDescription"] 	= $mbwayRequestTransactionResult->get_ErrorDescription();

			$this->logger->debug(
			[
				'result'	 	=> $mbwayRequestTransactionResult->get_MBWayPaymentOperationResult()->get_StatusCode(),
				'order_params' 	=> $parameters
			]
			);		
			if ($mbwayRequestTransactionResult->get_ErrorDescription() == "Invalid phone number" || $mbwayRequestTransactionResult->get_ErrorDescription() == "Merchant account does not exist.")
				$errorMessage = __($mbwayRequestTransactionResult->get_ErrorDescription());
			else
				$errorMessage = __('It was not possible to complete your payment. Please try again.');	

			throw new LocalizedException($errorMessage);

		}  

		$platform = $this->getPlatform();
        	$response = [
                'RESULT_CODE' 	=> $mbwayRequestTransactionResult->get_ErrorCode(),
                'ENTITY'	 	=> $entity,
                'MERCHANTID'	 	=> $merchantId,
                'REFERENCE' 		=> $transactionId,
                'PHONE' 		=> $customerPhone,
                'AMOUNTOUT' 		=> $amount,
                'ACCOUNT_TYPE' 	=> $platform,
                'CATEGORY_ID' 	=> $obj["MERCHANT_CREDENTIALS"]["merchant_api_category"],                
                'TRANSACTION_ID'	=> $this->generateTxnId($entity.$transactionId.date('YmdHis'))
                ];

	     if ($obj["DEBUG"])
		$this->logger->debug(
                [
		 'order_params' 	=> $parameters,
                'request' 		=> $transferObject->getBody(),
                'response' 		=> $response
                ]);

        return $response;
    }

    /**
     * Generates payment url
     *
     * @return array
     */
	private function _generatePaymentReference($parameters) {
 
		$this->ws_url = $this->_getEndpoint();
	
		try {
			$client = $this->soapClientFactory->create($this->ws_url);
			$result = $client->getReferenceMB ($parameters);
			return $result;
	        
		} catch (Exception $e) {
			return $e->getMessage();
		}

	}

    /**
     * @return string
     */
    protected function sanitizePhone($phone)
    {
	$phone = preg_replace("/[^0-9]/", "", $phone );
        return $phone;
    }
		         
    /**
     * @return string
     */
    protected function generateTxnId($source)
    {
        return md5($source);
    }

    /**
     * @return string
     */
    protected function getPlatform()
    {
        if (!$this->sandbox)
			return "PRODUCTION";
		else
			return "SANDBOX";
    }

}
