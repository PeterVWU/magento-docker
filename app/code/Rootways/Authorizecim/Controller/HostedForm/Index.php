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
namespace Rootways\Authorizecim\Controller\HostedForm;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Rootways\Authorizecim\Model\Request\Api as CustomApi;
use Rootways\Authorizecim\Model\Method\Logger as RwLogger;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Rootways\Authorizecim\Helper\Data $customHelper
     */
    protected $customHelper;

    /**
     * @var \Magento\Framework\App\Request\Http request
     */
    protected $request;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var CheckoutSession $session
     */
    protected $checkoutSession;

     /**
      * @var CustomApi
      */
    protected $customApi;

    /**
     * @var RwLogger
     */
    private $rwLogger;

    /**
     * @param Context $context
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param CheckoutSession $checkoutSession
     * @param CustomApi $customApi
     * @param RwLogger $rwLogger
     */
    public function __construct(
        Context $context,
        \Rootways\Authorizecim\Helper\Data $customHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        CheckoutSession $checkoutSession,
        CustomApi $customApi,
        RwLogger $rwLogger
    ) {
        $this->customHelper = $customHelper;
        $this->request = $request;
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customApi = $customApi;
        $this->rwLogger = $rwLogger;
        parent::__construct($context);
    }

    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $iframecommunicatorUrl = $this->customHelper->getIframeCommunicatorUrl();

        $VERIFYPEER = 'false';
        $VERIFYHOST = 'false';
        if ($this->customHelper->getEnvironment() == 'production') {
            $VERIFYPEER = 1;
            $VERIFYHOST = 2;
        }
        $_quote = $this->getQoute();
        $email = $this->request->getParam('email');
        $invoiceNum = date("dmYhis").'_'.random_int(10, 99);
        //$invoiceNum = $this->getRreserveIncId();
        $billTo = $shipTo = '';
        if ($this->customHelper->getConfig('payment/rootways_authorizecim_option_hosted/send_billing_address') &&
            !empty($this->request->getParam('billfirstname'))
        ) {
            $billFname = $this->formatValues($this->request->getParam('billfirstname'), 50);
            $billLname = $this->formatValues($this->request->getParam('billlastname'), 50);
            $billCom = $this->formatValues($this->request->getParam('billcompany'), 50);
            $billStr = '';
            if (!empty($this->request->getParam('billaddress'))) {
                $billStr = implode(" ", $this->request->getParam('billaddress'));
                $billStr = $this->formatValues($billStr, 60);
            }
            $billCity = $this->formatValues($this->request->getParam('billcity'), 40);
            $billState = $this->formatValues($this->request->getParam('billstate'), 40);
            $billZip = $this->formatValues($this->request->getParam('billzip'), 20);
            $billphone = $this->formatValues($this->request->getParam('billphone'), 25);
            $billTo = '
            <billTo>
                <firstName>'.$billFname.'</firstName>
                <lastName>'.$billLname.'</lastName>
                <company>'.$billCom.'</company>
                <address>'.$billStr.'</address>
                <city>'.$billCity.'</city>
                <state>'.$billState.'</state>
                <zip>'.$billZip.'</zip>
                <country>'.$this->request->getParam('billconid').'</country>
                <phoneNumber>'.$billphone.'</phoneNumber>
            </billTo>';
        }
        if ($this->customHelper->getConfig('payment/rootways_authorizecim_option_hosted/send_shipping_address') &&
            !empty($this->request->getParam('shipfirstname'))
        ) {
            $shipFname = $this->formatValues($this->request->getParam('shipfirstname'), 50);
            $shipLname = $this->formatValues($this->request->getParam('shiplastname'), 50);
            $shipCom = $this->formatValues($this->request->getParam('shipcompany'), 50);
            $shipStr = '';
            if (!empty($this->request->getParam('shipaddress'))) {
                $shipStr = implode(" ", $this->request->getParam('shipaddress'));
                $shipStr = $this->formatValues($shipStr, 60);
            }
            $shipCity = $this->formatValues($this->request->getParam('shipcity'), 40);
            $shipState = $this->formatValues($this->request->getParam('shipstate'), 40);
            $shipZip = $this->formatValues($this->request->getParam('shipzip'), 20);
            $shipTo = '
            <shipTo>
                <firstName>'.$shipFname.'</firstName>
                <lastName>'.$shipLname.'</lastName>
                <company>'.$shipCom.'</company>
                <address>'.$shipStr.'</address>
                <city>'.$shipCity.'</city>
                <state>'.$shipState.'</state>
                <zip>'.$shipZip.'</zip>
                <country>'.$this->request->getParam('shipconid').'</country>
            </shipTo>';
        }
        $xmlStr = '<?xml version="1.0" encoding="utf-8"?>
        <getHostedPaymentPageRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
            <merchantAuthentication></merchantAuthentication>
            <transactionRequest>
                <transactionType>authCaptureTransaction</transactionType>
                <amount>0</amount>';
                $xmlStr .= $this->getTransactionRequestDetails($email);
                $xmlStr .= '<order>
                    <invoiceNumber>'.$invoiceNum.'</invoiceNumber>
                    <description>'.$invoiceNum.'</description>
                </order>';

        if ($this->customHelper->sendCartItemHosted() == 1 && $_quote->getItems()) {
            $xmlStr .= '<lineItems>';
            foreach ($_quote->getItems() as $item) {
                if (!$item->getParentItem()) {
                    $taxable = 0;
                    if (!empty($item->getTaxAmount()) && (float) $item->getTaxAmount() > 0) {
                        $taxable = 1;
                    }
                    $xmlStr .= '<lineItem>
                                <itemId>' . $this->formatValues($item->getSku(), 31) . '</itemId>
                                <name>' . $this->formatValues($item->getName(), 31) . '</name>
                                <description>' . $this->formatValues($item->getName(), 254) . '</description>
                                <quantity>' . $item->getQty() . '</quantity>
                                <unitPrice>' . $item->getPrice() . '</unitPrice>
                                <taxable>' . $taxable . '</taxable>
                            </lineItem>';
                }
            }
            $xmlStr .= '</lineItems>';
        }

        if (!empty($_quote->getShippingAddress()->getTaxAmount()) && (float) $_quote->getShippingAddress()->getTaxAmount() > 0) {
            $xmlStr .= '<tax>
                        <amount>'.$_quote->getShippingAddress()->getTaxAmount().'</amount>
                    </tax>';
        }
        if (!empty($_quote->getShippingAddress()->getShippingAmount())) {
            if ((float) $_quote->getShippingAddress()->getShippingAmount() > 0) {
                $xmlStr .= '<shipping>
                        <amount>'.$_quote->getShippingAddress()->getShippingAmount().'</amount>
                        <name>'.$this->formatValues($_quote->getShippingAddress()->getShippingMethod(), 30).'</name>
                        <description>'.$this->formatValues($_quote->getShippingAddress()->getShippingDescription(), 254).'</description>
                    </shipping>';
            }
        }
                $xmlStr .= '<poNumber>'.$invoiceNum.'</poNumber>';
        if (!empty($email)) {
            $xmlStr .= '<customer>';
        }
        if (!empty($email) && $this->customHelper->getCustomerId() != '') {
            $xmlStr .= '<id>'.$this->customHelper->getCustomerId().'</id>';
        }
        if (!empty($email)) {
            $xmlStr .= '<email>'.$email.'</email></customer>';
        }

                $xmlStr .= $billTo.$shipTo.'
            </transactionRequest>
            <hostedPaymentSettings>
                <setting>
                    <settingName>hostedPaymentIFrameCommunicatorUrl</settingName>
                </setting>
                <setting>
                    <settingName>hostedPaymentButtonOptions</settingName>
                </setting>
                <setting>
                    <settingName>hostedPaymentReturnOptions</settingName>
                </setting>
                <setting>
                    <settingName>hostedPaymentOrderOptions</settingName>
                    <settingValue>{"show": false}</settingValue>
                </setting>
                <setting>
                    <settingName>hostedPaymentPaymentOptions</settingName>
                </setting>
                <setting>
                    <settingName>hostedPaymentBillingAddressOptions</settingName>
                    <settingValue>{"show": false, "required":false}</settingValue>
                </setting>
                <setting>
                    <settingName>hostedPaymentShippingAddressOptions</settingName>
                    <settingValue>{"show": false, "required":false}</settingValue>
                </setting>
                <setting>
                    <settingName>hostedPaymentSecurityOptions</settingName>
                    <settingValue>{"captcha": false}</settingValue>
                </setting>
                <setting>
                    <settingName>hostedPaymentStyleOptions</settingName>
                </setting>
                <setting>
                    <settingName>hostedPaymentCustomerOptions</settingName>
                    <settingValue>{"showEmail": false, "requiredEmail":true}</settingValue>
                </setting>
            </hostedPaymentSettings>
        </getHostedPaymentPageRequest>';


        $xml = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOWARNING);
        // $xml = new SimpleXMLElement($xmlStr);

        $xml->merchantAuthentication->addChild('name', $this->customHelper->getApiLoginId());
        $xml->merchantAuthentication->addChild('transactionKey', $this->customHelper->getTransactionKey());

        $xml->transactionRequest->transactionType = $this->customHelper->getHostedPaymentAction();
        $xml->transactionRequest->amount = $this->request->getParam('amount');
        $xml->transactionRequest->customerIP = $this->customHelper->getCustomerIp();

        $commUrl = $this->customHelper->getJsonEncode(['url' => $iframecommunicatorUrl], JSON_UNESCAPED_SLASHES);
        $xml->hostedPaymentSettings->setting[0]->addChild('settingValue', $commUrl);

        $btnTxt = $this->customHelper->getJsonEncode(['text' => $this->customHelper->hostedFormOrderBtnTxt()], JSON_UNESCAPED_SLASHES);
        $xml->hostedPaymentSettings->setting[1]->addChild('settingValue', $btnTxt);

        $retUrl = $this->customHelper->getJsonEncode(["showReceipt" => false , 'url' => $iframecommunicatorUrl, "urlText"=>"Continue to site", "cancelUrl" => $iframecommunicatorUrl, "cancelUrlText" => "Cancel" ], JSON_UNESCAPED_SLASHES);
        $xml->hostedPaymentSettings->setting[2]->addChild('settingValue', $retUrl);

        $showBankAccount = false;
        if ($this->customHelper->getConfig('payment/rootways_authorizecim_option_hosted/show_bankaccount')) {
            $showBankAccount = true;
        }
        $showBank = $this->customHelper->getJsonEncode(["cardCodeRequired" => true, "showBankAccount" => $showBankAccount], JSON_UNESCAPED_SLASHES);
        $xml->hostedPaymentSettings->setting[4]->addChild('settingValue', $showBank);

        $colorCode = "#3F8FCD";
        if ($this->customHelper->getConfig('payment/rootways_authorizecim_option_hosted/hosted_form_color') != '') {
            $colorCode = $this->customHelper->getConfig('payment/rootways_authorizecim_option_hosted/hosted_form_color');
        }
        $frmClr = $this->customHelper->getJsonEncode(['bgColor' => $colorCode], JSON_UNESCAPED_SLASHES);
        $xml->hostedPaymentSettings->setting[8]->addChild('settingValue', $frmClr);

        /*
        if ($this->customHelper->getConfig('payment/rootways_authorizecim_option_hosted/show_visacheckout')) {
            $visaCheckoutApiKey = $this->customHelper->getVisaCheckoutApiKey();
            $xml->hostedPaymentSettings->addChild('setting');
            $xml->hostedPaymentSettings->setting[10]->addChild('settingName', 'hostedPaymentVisaCheckoutOptions');
            $xml->hostedPaymentSettings->setting[10]->addChild('settingValue', '{"apiKey":"'.$visaCheckoutApiKey.'","displayName":"DISPNAME","message":"MESSAGE"}');
        }
        */

        $url = $this->customHelper->getGatewayUrl();
        $resultData = '';
        $responseDataArray = [];
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $VERIFYPEER);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $VERIFYHOST);
            curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
            //curl_setopt($ch, CURLOPT_PROXY, 'userproxy.visa.com:80');
            $content = curl_exec($ch);
            $content = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $content);
            $hostedPaymentResponse = new \SimpleXMLElement($content);
            $responseDataArray = $hostedPaymentResponse;

            if (isset($hostedPaymentResponse->token)) {
                $resultData = $hostedPaymentResponse->token;
            }
            if (false === $content) {
                $resultData = '';
                //throw new \Exception(curl_error($ch), curl_errno($ch));
            }
            curl_close($ch);
        } catch (\Exception $e) {
            $resultData = '';
            //trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
        }
        if ($this->customHelper->getConfig('payment/rootways_authorizecim_basic/debug')) {
            $requestDataXml = simplexml_load_string($xml->asXML(), 'SimpleXMLElement', LIBXML_NOWARNING);
            $requestDataJson = json_encode($requestDataXml);
            $requestDataArray = json_decode($requestDataJson, true);
            $reqLog = ["Request" => $requestDataArray, "Response" => $responseDataArray];
            $this->rwLogger->debug($reqLog);
        }

        $result = $this->resultRawFactory->create();

        // Return Raw Text or HTML data
        $result->setContents($resultData);
        return $result;
    }

    /**
     * @return \Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQoute()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Reserve order ID for specified quote
     */
    public function getRreserveIncId()
    {
        $quote = $this->getQoute();
        $quote->reserveOrderId();
        $quote->save($quote);

        $invoiceNum = $quote->getReservedOrderId();
        if ($invoiceNum == '') {
            $invoiceNum = date("dmYhis").'_'.random_int(10, 99);
        }

        return $invoiceNum . '_' . random_int(100, 999);
    }

    /**
     * Placeholder for custom extensions to interject details into the response
     *
     * @return string
     */
    protected function getTransactionRequestDetails($email)
    {
        $allowSaveCc = $this->customHelper->getConfig("payment/rootways_authorizecim_option_hosted/save_cc");
        if (empty($allowSaveCc)) {
            return '';
        }
        $customerProfileId = $this->customApi->getCustomerProfileId($email);
        if (!empty($customerProfileId)) {
            return '<profile>
                <customerProfileId>' . $customerProfileId . '</customerProfileId>
            </profile>';
        }

        return '';
    }

    /**
     * @param $val
     * @param $len
     * @return mixed
     */
    protected function formatValues($val, $len)
    {
        if (!empty($val) && strlen($val) >= $len) {
            $val = mb_substr(htmlspecialchars(trim($val)), 0, $len);
        }
        $val = !empty($val) ? htmlspecialchars($val) : $val;

        return  $val;
    }
}
