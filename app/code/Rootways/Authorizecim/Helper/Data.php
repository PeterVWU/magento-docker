<?php
/**
 * Authorize.net Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\UrlInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\State as appState;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\App\ResourceConnection;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     *
     * @var \Magento\Customer\Model\Session
     */
    public $_customerSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $resourceConfig;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $_regionFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     *
     * @var \Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory
     */
    public $vaultCollectionFactory;

    /**
     * @var PaymentConfig
     */
    protected $paymentConfig;

     /**
      * @var RemoteAddress
      */
    protected $remoteAddress;

    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var Json
     */
    protected $_json;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var BackendHelper
     */
    protected $backendHelper;

    /**
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $moduleReader;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Session quote
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory $vaultCollectionFactory,
        \Magento\Framework\Module\Dir\Reader $moduleReader,
        BackendHelper $backendHelper,
        PaymentConfig $paymentConfig,
        RemoteAddress $remoteAddress,
        UrlInterface $urlInterface,
        Json $json,
        AppState $appState,
        ResourceConnection $resourceConnection,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        Filesystem $filesystem
    ) {
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_encryptor = $encryptor;
        $this->resourceConfig = $resourceConfig;
        $this->_regionFactory = $regionFactory;
        $this->_countryFactory = $countryFactory;
        $this->vaultCollectionFactory = $vaultCollectionFactory;
        $this->moduleReader = $moduleReader;
        $this->backendHelper = $backendHelper;
        $this->paymentConfig = $paymentConfig;
        $this->remoteAddress = $remoteAddress;
        $this->_urlInterface = $urlInterface;
        $this->_json = $json;
        $this->appState = $appState;
        $this->resourceConnection = $resourceConnection;
        $this->_sessionQuote = $sessionQuote;
        $this->_filesystem = $filesystem;
        parent::__construct($context);
    }

    public function getConfig($config_path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Authorize.net payment gateway environment.
     */
    public function getEnvironment($storeId = null)
    {
        return $this->getConfig('payment/rootways_authorizecim_basic/environment', $storeId);
    }

    /**
     * Authorize.net payment gateway URL.
     *
     * @return string
     */
    public function getGatewayUrl($storeId = null)
    {
        if ($this->getEnvironment($storeId) == "production") {
            if ($this->getConfig('payment/rootways_authorizecim_basic/gateway_url', $storeId) != '') {
                $url = $this->getConfig('payment/rootways_authorizecim_basic/gateway_url', $storeId);
            } else {
                $url = "https://api.authorize.net/xml/v1/request.api";
            }
        } else {
            $url = 'https://apitest.authorize.net/xml/v1/request.api';
        }
        return $url;
    }

    /**
     * Authorize.net Hosted payment gateway URL.
     *
     * @return string
     */
    public function getHostedGatewayUrl()
    {
        if ($this->getEnvironment() == "production") {
            $url = "https://accept.authorize.net/payment/payment";
        } else {
            $url = "https://test.authorize.net/payment/payment";
        }
        return $url;
    }

    /**
     * Get API Login ID
     */
    public function getApiLoginId($storeId = null)
    {
        return $this->getConfig('payment/rootways_authorizecim_basic/api_login_id', $storeId);
    }

    /**
     * Get API Transaction Key
     */
    public function getTransactionKey($storeId = null)
    {
        $transaction_key = $this->getConfig('payment/rootways_authorizecim_basic/api_trans_key', $storeId);
        return $this->_encryptor->decrypt($transaction_key);
    }

    /**
     * Get API Client Key
     */
    public function getApiClientKey($storeId = null)
    {
        return $this->getConfig('payment/rootways_authorizecim_basic/api_client_key', $storeId);
    }

    /**
     * Authorize.net payment action type.
     */
    public function getPaymentAction()
    {
        $action = 'authCaptureTransaction';
        if ($this->getConfig('payment/rootways_authorizecim_option/payment_action', $this->getStoreId()) == "authorize") {
            $action = 'authOnlyTransaction';
        }

        return $action;
    }

    /**
     * Authorize.net Hosted Method Payment Action Type.
     */
    public function getHostedPaymentAction()
    {
        $action = 'authCaptureTransaction';
        if ($this->getConfig('payment/rootways_authorizecim_option_hosted/payment_action', $this->getStoreId()) == "authorize") {
            $action = 'authOnlyTransaction';
        }

        return $action;
    }

    /**
     * Authorize.net eCheck Method Payment Action Type.
     */
    public function getEcheckPaymentAction()
    {
        $action = 'authCaptureTransaction';
        if ($this->getConfig('payment/rootways_authorizecim_echeck/payment_action', $this->getStoreId()) == "authorize") {
            $action = 'authOnlyTransaction';
        }

        return $action;
    }

    /**
     * Check deos Accept.js is enabled or not
     *
     * @return int
     */
    public function enableAcceptjs()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/acceptjs', $this->getStoreId()) && $this->checkAdmin() != 'adminhtml' ? 1 : 0;
    }

    /**
     * Check deos AcceptUI.js is enabled or not
     *
     * @return bool
     */
    public function enableAcceptUijs():bool
    {
        return $this->getConfig('payment/rootways_authorizecim_option/form_type', $this->getStoreId()) == 'iframe' && $this->checkAdmin() != 'adminhtml' ? 1 : 0;
    }

    /**
     * AcceptUI.js pop up header text
     *
     * @return string
     */
    public function enableAcceptUiHeaderTxt()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/hosted_popup_header_txt', $this->getStoreId());
    }

    /**
     * AcceptUI.js pop up pay button text
     *
     * @return string
     */
    public function enableAcceptUiButTxt()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/hosted_popup_btn_txt', $this->getStoreId());
    }

    /**
     * Check deos hosted form enabled or not
     *
     * @return int
     */
    public function enableHostedForm()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/enabled_hostedform', $this->getStoreId()) && $this->checkAdmin() != 'adminhtml' ? 1 : 0;
    }

    /**
     * Get text of hosted form submit button
     */
    public function hostedFormOrderBtnTxt()
    {
        $payBtnTxt = 'Pay';
        if ($this->getConfig('payment/rootways_authorizecim_option_hosted/hosted_form_order_btn_txt', $this->getStoreId()) != '') {
            $payBtnTxt = $this->getConfig('payment/rootways_authorizecim_option_hosted/hosted_form_order_btn_txt', $this->getStoreId());
        }

        return $payBtnTxt;
    }

    /**
     * Get text of hosted form header.
     */
    public function hostedFormHeader()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_hosted/hosted_form_header', $this->getStoreId());
    }

    /**
     * Get VISA CHECKOUT API Key
     *
     * @return string
     */
    public function getVisaCheckoutApiKey()
    {
        $val = $this->getConfig('payment/rootways_authorizecim_option_hosted/visacheckout_api_key', $this->getStoreId());
        return $this->_encryptor->decrypt($val);
    }

    /**
     * Get VISA CHECKOUT API Key
     *
     * @return string
     */
    public function getVisaCheckoutMethodApiKey()
    {
         $this->getStoreId();
        $val = $this->getConfig('payment/rootways_authorizecim_option_visa/visacheckout_api_key', $this->getStoreId());
        return $this->_encryptor->decrypt($val);
    }

    /**
     * Get VISA Accepted Currency
     *
     * @return string
     */
    public function getVisaCheckoutCurrency()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_visa/currency', $this->getStoreId());
    }

    /**
     * VISA Checkout payment action type.
     */
    public function getVisaCheckoutPaymentAction()
    {
        $action = 'authCaptureTransaction';
        if ($this->getConfig('payment/rootways_authorizecim_option_visa/payment_action', $this->getStoreId()) == "authorize") {
            $action = 'authOnlyTransaction';
        }
        return $action;
    }

    /**
     * VISA Checkout Popup Language.
     */
    public function getVisaCheckoutLocale()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_visa/locale', $this->getStoreId());
    }

    /**
     * VISA Checkout Top Message.
     */
    public function getVisaTopNote()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_visa/msg', $this->getStoreId());
    }

    /**
     * Credit Card Payment Method Top Message.
     */
    public function getCCMethodTopNote()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/msg', $this->getStoreId());
    }

    /**
     * Hosted Form Top Message.
     */
    public function getHostedMethodTopNote()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_hosted/msg', $this->getStoreId());
    }

    /**
     * Authorize.net Google Pay payment action type.
     */
    public function getGooglePayPaymentAction()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_googlepay/payment_action', $this->getStoreId()) == "authorize" ?
                'authOnlyTransaction' :
                'authCaptureTransaction';
    }

    public function isGooglePayActive()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_googlepay/active', $this->getStoreId());
    }

    public function getGooglePayCurrency()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_googlepay/currency', $this->getStoreId());
    }

    public function getGooglePayMerchantCountryCode()
    {
        $con = $this->getConfig('payment/rootways_authorizecim_option_googlepay/merchant_country_code', $this->getStoreId());
        if ($con == '') {
            $con = 'US';
        }
        return $con;
    }




    /**
     * Get does vault enabled or not
     */
    public function vaultEnable()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_cc_vault/active', $this->getStoreId());
    }

    /**
     * Get does billing data is in request or not
     */
    public function sendBilling()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/send_billing_address', $this->getStoreId());
    }

    /**
     * Get does shipping data is in request or not
     */
    public function sendShipping()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/send_shipping_address', $this->getStoreId());
    }

    /**
     * Get does cart item data is in request or not
     */
    public function sendCartItem()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/send_cart_items', $this->getStoreId());
    }

    /**
     * Get does cart item data is in hosted form request or not
     */
    public function sendCartItemHosted()
    {
        return $this->getConfig('payment/rootways_authorizecim_option_hosted/send_cart_items', $this->getStoreId());
    }

    /**
     * Get value of licence key from admin
     */
    public function licencekey()
    {
        return $this->getConfig('rootways_authorizecim/general/licencekey');
    }

    /**
     * Get store base URL.
     */
    public function getStoreBaseUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function getStoreBackEndBaseUrl() {
        return $this->backendHelper->getHomePageUrl();
    }

    public function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }

    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function isJson($string){
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }

    /**
     * Get value of licence key from admin
     */
    public function act()
    {
        if ($this->getConfig('rootways_authorizecim/general/lcstatus') == '') {
            return "SXNzdWUgd2l0aCB5b3VyIFJvb3R3YXlzIGV4dGVuc2lvbiBsaWNlbnNlIGtleSwgcGxlYXNlIGNvbnRhY3QgPGEgaHJlZj0ibWFpbHRvOmhlbHBAcm9vdHdheXMuY29tIj5oZWxwQHJvb3R3YXlzLmNvbTwvYT4=";
        }

        return '';
    }

    /**
     * return area code.
     */
    public function checkAdmin()
    {
        return $this->appState->getAreaCode();
    }

    /**
     * Get value of secure URL from admin
     */
    public function surl()
    {
        return "aHR0cHM6Ly93d3cucm9vdHdheXMuY29tL3ZhbGlkYXRla2V5YnljbGljay5waHA=";
    }

    /**
     * Get value of Region Code.
     */
    public function getRegionCode($shipperRegionId)
    {
        $shipperRegion = $this->_regionFactory->create()->load($shipperRegionId);
        return $shipperRegion->getCode();
    }

    /**
     * @param $regionCode
     * @param $countryCode
     * @return \Magento\Directory\Model\Region
     */
    public function loadRegionByCode($regionCode, $countryCode)
    {
        $region = $this->_regionFactory->create()->loadByCode($regionCode, $countryCode);

        return $region;
    }

    /**
     * Get value of Country ID.
     */
    public function getCountryName($countryCode)
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);

        return $country->getName();
    }

    /**
     * Retrieve credit card name by code.
     */
    public function getCcTypeNameByCode($code)
    {
        $ccTypes = $this->paymentConfig->getCcTypes();

        return isset($ccTypes[$code]) ? $ccTypes[$code] : false;
    }

    /**
     * Retrieve credit card code by name.
     */
    public function getCcTypeCodeByName($name)
    {
        if (strtolower(str_replace(' ', '', $name)) == "americanexpress") {
            $ccCode = "AE";
        } else {
            $ccTypes = $this->paymentConfig->getCcTypes();
            $key = array_search(strtolower($name), array_map('strtolower', $ccTypes));
            $ccCode = $key != '' ? $key : false;
        }

        return $ccCode;
    }

    /**
     * Retrieve credit card types.
     */
    public function getCcTypes()
    {
        $ccTypes = $this->paymentConfig->getCcTypes();
        if (is_array($ccTypes)) {
            return $ccTypes;
        } else {
            return false;
        }
    }

    /**
     * Retrieve selected credit card from config.
     */
    public function getCcAvailableCardTypes($country = null)
    {
        $cType = $this->getConfig('payment/rootways_authorizecim_option/cctypes', $this->getStoreId());
        if (empty($cType)) {
            return [];
        }
        $types = array_flip(explode(',', $cType));
        $mergedArray = [];

        if (is_array($types)) {
            foreach (array_keys($types) as $type) {
                $types[$type] = $this->getCcTypeNameByCode($type);
            }
        }

        //preserve the same credit card order
        $allTypes = $this->getCcTypes();
        if (is_array($allTypes)) {
            foreach ($allTypes as $ccTypeCode => $ccTypeName) {
                if (array_key_exists($ccTypeCode, $types)) {
                    $mergedArray[$ccTypeCode] = $ccTypeName;
                }
            }
        }

        return $mergedArray;
    }

    /**
     * Retrieve selected credit card from Google Pay config.
     */
    public function getGooglePayCcAvailableCardTypes($country = null)
    {
        $cType = $this->getConfig('payment/rootways_authorizecim_option_googlepay/cctypes', $this->getStoreId());
        if (empty($cType)) {
            return [];
        }
        $types = array_flip(explode(',', $cType));

        $cardNames = [
            'AE' => 'AMEX',
            'VI' => 'VISA',
            'MC' => 'MASTERCARD',
            'DI' => 'DISCOVER',
            'JCB' => 'JCB'
        ];

        $mapped = [];

        // Loop through the card codes and map the corresponding names
        foreach ($types as $code => $value) {
            if (isset($cardNames[$code])) {
                // Push the name to the result array (using the value as an index is no longer necessary)
                $mapped[] = $cardNames[$code];
            }
        }
        return $mapped;
    }

    /**
     * Get customer saved credit card collection
     */
    public function getSavedCreditCard()
    {
        $customerId = '';
        if ($this->_customerSession->isLoggedIn()) {
            $customerId = $this->_customerSession->getId();
        }
        $cardcollection = $this->vaultCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('payment_method_code', 'rootways_authorizecim_option');
            //->addFieldToFilter('is_active', 1)
            //->addFieldToFilter('is_visible', 1)

        return $cardcollection;
    }

    /**
     * Get logged in customer ID.
     *
     * @return string.
     */
    public function getCustomerId()
    {
        $customerId = '';
        if ($this->checkAdmin() == 'adminhtml') {
            if ($this->_getAdminSession()->getCustomerId()) {
                $customerId = $this->_getAdminSession()->getCustomerId();
            }
        } else {
            if ($this->_customerSession->isLoggedIn()) {
                $customerId = $this->_customerSession->getId();
            }
        }

        return $customerId;
    }

    /**
     * Retrieve quote session object
     *
     * @return \Magento\Backend\Model\Session\Quote
     */
    protected function _getAdminSession()
    {
        return $this->_sessionQuote;
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return (bool) $this->_customerSession->isLoggedIn();
    }

    /**
     *  Send customer ID in proper formate
     */
    public function formatedCustomerId($id)
    {
        return substr($id, 0, strpos($id, "_"));
    }

    /**
     *  Request field validation
     */
    public function subStrFun($val, $len)
    {
        if (!empty($val) && strlen($val) >= $len) {
            $val = substr($val, 0, $len);
        }

        return $val;
    }

    /**
     * Get payment ID using payment token
     */
    public function getPaymentIdByToken($paymentToken)
    {
        $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
        $paymentId = '';
        if (isset($tokenDetails['paymentProfileId'])) {
            $paymentId = $tokenDetails['paymentProfileId'];
        }

        return $paymentId;
    }

    public function getQuote($quote)
    {
        // Get Currency and Amount
        $amount = $quote->getGrandTotal();
        $currency = $quote->getQuoteCurrencyCode();
        // Below code is not used anywhere in this module
        return [
            'currency' => 'usd',
            'total' => [
                'label' => 'Order by vish v',
                'amount' => $amount,
                'pending' => false
            ],
            'displayItems' => [
                'label' => __('TEST BY VISH'),
                'amount' => $amount
            ]
        ];
    }

    public function getCustomerIp()
    {
        if (getenv('HTTP_CLIENT_IP')) {
            $ipaddress = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipaddress = trim($ips[0]); //real IP address behind proxy IP
        } elseif (getenv('HTTP_X_FORWARDED')) {
            $ipaddress = getenv('HTTP_X_FORWARDED');
        } elseif (getenv('HTTP_FORWARDED_FOR')) {
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        } elseif (getenv('HTTP_FORWARDED')) {
            $ipaddress = getenv('HTTP_FORWARDED');
        } elseif (getenv('REMOTE_ADDR')) {
            $ipaddress = getenv('REMOTE_ADDR');
        } else {
            $ipaddress = 'UNKNOWN';
        }

        if (strlen($ipaddress) >= 15) {
            $ipaddress = substr($ipaddress, 0, 15);
        }

        return $ipaddress;
    }

    /**
     * @return boolean
     */
    public function getEnableCaptcha()
    {
        return $this->getConfig('payment/rootways_authorizecim_option/enable_captcha', $this->getStoreId());
    }

    /**
     * @return string
     */
    public function getCaptchaSiteKey()
    {
        if ($this->getEnableCaptcha() == 3) {
            $val = $this->getConfig('payment/rootways_authorizecim_option/v3_site_key', $this->getStoreId());
        } else {
            $val = $this->getConfig('payment/rootways_authorizecim_option/site_key', $this->getStoreId());
        }
        return $this->_encryptor->decrypt($val);
    }

    /**
     * @return string
     */
    public function getCaptchaSecretKey()
    {
        if ($this->getEnableCaptcha() == 3) {
            $val = $this->getConfig('payment/rootways_authorizecim_option/v3_secret_key', $this->getStoreId());
        } else {
            $val = $this->getConfig('payment/rootways_authorizecim_option/secret_key', $this->getStoreId());
        }
        return $this->_encryptor->decrypt($val);
    }

    public function verifyGC($gCaptcha)
    {
        $isWrongCaptcha = 1;
        if (!empty($gCaptcha)) {
            $post_data = http_build_query(
                [
                    'secret' => $this->getCaptchaSecretKey(),
                    'response' => $gCaptcha,
                    'remoteip' => $this->getCustomerIp()
                ]
            );

            $opts = ['http' =>
                [
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $post_data
                ]
            ];

            $context  = stream_context_create($opts);
            $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
            $result = json_decode($response);

            if (isset($result->success)) {
                if ($this->getEnableCaptcha() == 3) {
                    if (isset($result->score)) {
                        $captchaMinScore = 0.5;
                        $captchaScore = $this->getConfig('payment/rootways_authorizecim_option/c_minimum_score', $this->getStoreId());
                        if ($captchaScore != '') {
                            $captchaMinScore = $captchaScore;
                        }
                        if ($result->success == 1 && (float)$result->score >= (float)$captchaMinScore) {
                            $isWrongCaptcha = 0;
                        }
                    }
                } else {
                    if ($result->success == 1) {
                        $isWrongCaptcha = 0;
                    }
                }
            }
        }

        $captchaErrorMsg = '';
        if ($isWrongCaptcha == 1) {
            $captchaErrorMsg = __('reCAPTCHA verification failed.');
            $v2Msg = $this->getConfig('payment/rootways_authorizecim_option/v2_validation_failure_message', $this->getStoreId());
            $v3Msg = $this->getConfig('payment/rootways_authorizecim_option/v3_validation_failure_message', $this->getStoreId());
            if ($this->getEnableCaptcha() == 3) {
                $captchaErrorMsg = __('You cannot proceed with such operation, your reCAPTCHA reputation is too low.');
                if ($v3Msg != '') {
                    $captchaErrorMsg = $v3Msg;
                }
            } else {
                $captchaErrorMsg = __('reCAPTCHA verification failed.');
                if ($v2Msg != '') {
                    $captchaErrorMsg = $v2Msg;
                }
            }
        }

        return $captchaErrorMsg;
    }

    /**
     * @param $data
     * @return bool|false|string
     */
    public function getJsonEncode($data)
    {
        return $this->_json->serialize($data);
    }

    /**
     * @param $data
     * @return array|bool|float|int|mixed|string|null
     */
    public function getJsonDecode($data)
    {
        return $this->_json->unserialize($data);
    }

    public function getIframeCommunicatorUrl()
    {
        return $this->_urlInterface->getUrl('rootways_authorizecim/hostedform/iframecommunicator');
    }

    /**
     * @return string
     */
    public function getRwModuleDir()
    {
        $etcDir = $this->moduleReader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
            'Rootways_Authorizecim'
        );

        return $etcDir;
    }

    /**
     * Select query to fetch records
     *
     * @return array
     */
    public function getAagreementCollection()
    {
        $tableName = $this->resourceConnection->getTableName('checkout_agreement');
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['c' => $tableName],
                ['*']
            )
            ->where(
                "c.is_active = 1"
            )->where(
                "c.mode = 1"
            );
        $records = $connection->fetchAll($select);

        return $records;
    }

    /** Get Admin Store ID */
    public function getAdminStoreId()
    {
        if ($this->_request->getParam('store') != '') {
            $storeId = (int) $this->_request->getParam('store');
        } elseif ($this->_request->getParam('website') != '') {
            $websiteId = $this->_request->getParam('website');
            $storeId = (int) $this->_storeManager->getWebsite($websiteId)->getDefaultStore()->getId();
        } else {
            $storeId = 0;
        }

        return $storeId;
    }

    /**
     * @return string
     */
    public function getStreetAddress($address)
    {
        if (!empty($address->getStreetLine1())) {
            $strAdd = $address->getStreetLine1() .' '.$address->getStreetLine2();
        } else {
            $strAdd = implode(" ", $address->getStreet());
        }

        return $strAdd;
    }

    /**
     * @return array
     */
    public function getFormattedAddress($address, $type = 'billTo')
    {
        $formattedAddress =  [
            'firstName' => $this->subStrFun($address->getFirstname(), 50),
            'lastName' =>  $this->subStrFun($address->getLastname(), 50),
            'company' =>  $this->subStrFun($address->getCompany(), 50),
            'address' => $this->subStrFun($this->getStreetAddress($address), 60),
            'city' => $this->subStrFun($address->getCity(), 40),
            'state' => $this->subStrFun($address->getRegionCode(), 40),
            'zip' => $this->subStrFun($address->getPostcode(), 20),
            'country' => $address->getCountryId(),
        ];

        if ($type == 'billTo') {
            $formattedAddress['phoneNumber'] = $this->subStrFun($address->getTelephone(), 25);
        }

        return $formattedAddress;
    }

    /**
     * @return string[]
     */
    public function getAchAccountTypes()
    {
        return [
            'checking' => 'Checking',
            'savings' => 'Savings',
            'businessChecking' => 'Business Checking'
        ];
    }

    /**
     * @return string
     */
    public function getAuthorizenetSolutionID()
    {
        return $this->getEnvironment() == "production" ? 'AAA183485' : 'AAA100302';
    }

    /**
     * @return string
     */
    public function getMediaPath()
    {
        return $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
    }

    /**
     * @param $result
     * @return array|mixed
     */
    public function validateXmlResponse($result)
    {
        $newPaymentResponse = [];
        libxml_use_internal_errors(true);
        $new = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
        if ($new !== false) {
            $con = json_encode($new);
            $newPaymentResponse = json_decode($con, true);
        }
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        return $newPaymentResponse;
    }
}
