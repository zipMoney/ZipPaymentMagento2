<?php

namespace Zip\ZipPayment\Model\Config;

use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\HTTP\Adapter\CurlFactory;

/**
 * Admin Model of health check
 *
 * @author  Zip Co - Plugin Team <integrations@zip.co>
 **/
class HealthCheck
{
    const STATUS_SUCCESS = 1;
    const STATUS_WARNING = 2;
    const STATUS_ERROR = 3;
    const STATUS_OK = 0;

    const SSL_DISABLED_MESSAGE = 'Your store {store_name} ({store_url}) does not have SSL';
    const CURL_EXTENSION_DISABLED = 'CURL extension has not been installed or disabled';
    const API_CERTIFICATE_INVALID_MESSAGE = 'SSL Certificate is not valid for the API';
    const API_PRIVATE_KEY_INVALID_MESSAGE = 'Your API private key is empty or invalid';
    const API_CREDENTIAL_INVALID_MESSAGE = 'Your API credential is invalid';
    const MERCHANT_COUNTRY_NOT_SUPPORTED_MESSAGE = 'Your merchant country not been supported';

    protected $_result = [
        'overall_status' => self::STATUS_SUCCESS,
        'items' => []
    ];

    protected $_region = [
        'au' => 'Australia',
        'nz' => 'New Zealand',
        'us' => 'United States',
        'uk' => 'United Kingdom',
        'za' => 'South Africa',
        'ca' => 'Canada',
        'ae' => 'United Arab Emirate',
        'mx' => 'Mexico',
        'sa' => 'Saudi Arabia',
        'cz' => 'Czech Republic',
        'pl' => 'Poland',
    ];

    /**
     * @var \Zip\ZipPayment\Helper\Logger
     */
    protected $_logger;

    /**
     * @var \Zip\ZipPayment\Model\Config
     */
    protected $_config;
    /**
     * @var \Zip\ZipPayment\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var CurlFactory
     */
    private $_curlFactory;
    /**
     * @var \Zend\Uri\Uri
     */
    private $_zendUri;

    /**
     * HealthCheck constructor.
     * @param \Zip\ZipPayment\Helper\Logger $logger
     * @param \Zip\ZipPayment\Helper\Data $helper
     * @param \Zip\ZipPayment\Model\Config $config
     * @param CurlFactory $curlFactory ,
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Zend\Uri\Uri $zendUri
     */
    public function __construct(
        \Zip\ZipPayment\Helper\Logger $logger,
        \Zip\ZipPayment\Helper\Data $helper,
        \Zip\ZipPayment\Model\Config $config,
        CurlFactory $curlFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Zend\Uri\Uri $zendUri
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_config = $config;
        $this->_curlFactory = $curlFactory;
        $this->_storeManager = $storeManager;
        $this->_zendUri = $zendUri;
    }

    /**
     * check multiple items and get health result
     */
    public function getHealthResult($websiteId, $apiKey = null, $publicKey = null, $env = null)
    {
        /** @var Curl $curlObject */
        $curlObject = $this->_curlFactory->create();
        // Configure API Credentials
        $apiConfig = \Zip\ZipPayment\MerchantApi\Lib\Configuration::getDefaultConfiguration();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);

        $storeId = $storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        $publicKey = $publicKey ?? $this->_config->getMerchantPublicKey($storeId);
        $privateKey = $apiKey ?? $this->_config->getMerchantPrivateKey($storeId);
        $environment = $env ?? $this->_config->getEnvironment($storeId);
        $apiConfig->setApiKey('Authorization', $privateKey)
            ->setApiKeyPrefix('Authorization', 'Bearer')
            ->setEnvironment($environment)
            ->setPlatform("Magento/" . $this->_helper->getMagentoVersion()
                . "Zip_ZipPayment/" . $this->_helper->getExtensionVersion());

        $curlEnabled = function_exists('curl_version');

        // check if private key is empty
        if (empty($privateKey)) {
            $this->appendItem(self::STATUS_ERROR, __(self::API_PRIVATE_KEY_INVALID_MESSAGE));
        }

        // check whether SSL is enabled
        $this->checkStoreSSLSettings();

        // check whether CURL is enabled ot not
        if (!$curlEnabled) {
            $this->appendItem(self::STATUS_ERROR, __(self::CURL_EXTENSION_DISABLED));
        } else {
            $curlObject->setConfig(
                [
                    'timeout' => 10,
                ]
            );

            try {
                $apiConfig->setCurlTimeout(30);
                $headers = [
                    'Authorization: ' .
                        $apiConfig->getApiKeyPrefix('Authorization') .
                        ' ' .
                        $apiConfig->getApiKey('Authorization'),
                    'Accept : application/json',
                    'Zip-Version: 2017-03-01',
                    'Content-Type: application/json',
                    'Idempotency-Key: ' . uniqid()
                ];
                $url = $apiConfig->getHost() . '/me';
                $isAuEndpoint = false;

                // check api key length if it is more than or equal 50 then call SMI merchant info endpoint
                // otherwise call checkout get api endpoint only for Australia
                if (strlen($privateKey) <= 50) {
                    $checkoutId = 'au-co_PxSeQfLlpaYn6bLMZSMv13';
                    $url = $apiConfig->getHost() . '/checkouts/' . $checkoutId;
                    $isAuEndpoint = true;
                }
                $curlObject->write(\Zend_Http_Client::GET, $url, '1.1', $headers);
                $response = $curlObject->read();
                $sslVerified = $curlObject->getInfo(CURLINFO_SSL_VERIFYRESULT) == 0;
                $httpCode = (int)$curlObject->getInfo(CURLINFO_HTTP_CODE);
                // if API certification invalid
                if (!$sslVerified) {
                    $this->appendItem(self::STATUS_WARNING, __(self::API_CERTIFICATE_INVALID_MESSAGE));
                }

                // if API call is failed
                if ($httpCode == 0) {
                    $this->appendItem(self::STATUS_ERROR, 'API call to ' . $url . ' failed');
                }
                // if API credential is invalid
                if ($httpCode == 401 || $httpCode == 403) {
                    $this->appendItem(self::STATUS_ERROR, __(self::API_CREDENTIAL_INVALID_MESSAGE));
                }
                if (($httpCode >= 200 && $httpCode <= 299) && $isAuEndpoint == false) {
                    $result = preg_split('/^\r?$/m', $response, 2);
                    $result = trim($result[1]);
                    $data = json_decode($result);
                    $this->appendItem(self::STATUS_OK, "Api key is valid for " . $data->name);
                    $regions = $data->regions;
                    if ($regions) {
                        $regionList = 'Api is valid for below regions:<br>';
                        foreach ($regions as $region) {
                            $regionList .= $this->_region[$region] . '<br>';
                        }
                        $this->appendItem(self::STATUS_OK, $regionList);
                    }
                }

                if (($httpCode == 404 || ($httpCode >= 200 && $httpCode <= 299)) && $isAuEndpoint == true) {
                    $this->appendItem(self::STATUS_OK, "Api key is valid for Australia region.");
                }
            } catch (\Exception $e) {
                $this->_logger->error($e->getMessage());
                $this->appendItem(self::STATUS_ERROR, "Error occurred, Please try again.");
            }

            $curlObject->close();
        }

        usort(
            $this->_result['items'],
            function ($a, $b) {
                return $b['status'] - $a['status'];
            }
        );

        return $this->_result;
    }

    /**
     * append success and failed item into health result
     */
    protected function appendItem($status, $label)
    {
        if ($status !== null && $this->_result['overall_status'] < $status) {
            $this->_result['overall_status'] = $status;
        }

        $this->_result['items'][] = [
            "status" => $status,
            "label" => $label
        ];
    }

    protected function checkStoreSSLSettings()
    {
        $groups = $this->_storeManager->getWebsite()->getGroups();
        foreach ($groups as $key => $group) {
            $stores = $group->getStores();
            foreach ($stores as $store) {
                if ($store->getIsActive() !== '1'
                    || $this->_config->isMethodActive($store->getStoreId()) !== true
                ) {
                    continue;
                }

                $storeSecureUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
                $url = $this->_zendUri->parse($storeSecureUrl);
                if ($url->getScheme() !== 'https') {
                    $message = __(self::SSL_DISABLED_MESSAGE);
                    $message = str_replace('{store_name}', $store->getName(), $message);
                    $message = str_replace('{store_url}', $storeSecureUrl, $message);

                    $this->appendItem(
                        self::STATUS_WARNING,
                        $message
                    );
                }
            }
        }
    }
}
