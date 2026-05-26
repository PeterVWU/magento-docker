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
namespace Rootways\Authorizecim\Model;

use Magento\Framework\Exception\LocalizedException;
use Rootways\Authorizecim\Model\Method\Logger as RwLogger;

/**
 * Class HostedPayment
 */
class HostedPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE_HOSTED = "rootways_authorizecim_option_hosted";

    protected $_code = self::CODE_HOSTED;
    protected $_infoBlockType = 'Rootways\Authorizecim\Block\InfoHosted';

    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping  = true;
    protected $_canSaveCc = false;
    protected $_canReviewPayment = false;
    protected $_canCancelInvoice = true;

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @var \Rootways\Authorizecim\Model\Request\Api
     */
    protected $customApi;

    /**
     * @var RwLogger
     */
    protected $rwLogger;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param RwLogger $rwLogger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     **/
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Rootways\Authorizecim\Helper\Data $customHelper,
        \Rootways\Authorizecim\Model\Request\Api $customApi,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        RwLogger $rwLogger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->customHelper = $customHelper;
        $this->customApi = $customApi;
        $this->rwLogger = $rwLogger;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($this->customHelper->checkAdmin() == 'adminhtml') {
            return false;
        }

        return parent::isAvailable($quote);
    }

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            throw new LocalizedException(__('Error: Invalid amount for capture.'));
        }

        $posts = $payment->getData('additional_information');
        if (empty($posts['payment_method_nonce'])) {
            throw new LocalizedException(__('Error Processing the request. No response data found from the authorize.net server'));
        }
        $resultJsonData = $posts['payment_method_nonce'];
        $resultData = json_decode($resultJsonData, true);

        if ($this->customHelper->getConfig('payment/rootways_authorizecim_basic/debug')) {
            $reqLog = ["Request" => 'No request data. Only response data is available as this is a log data of iFrame response.', "Response" => $resultData];
            $this->rwLogger->debug($reqLog);
        }

        if (!isset($resultData['transId'])) {
            throw new LocalizedException(__('Error Processing the request.'));
        } else {
            if ($resultData['responseCode'] == '1' || $resultData['responseCode'] == '4') {
                $payment->setTransactionId($resultData['transId']);
                $payment->setCcTransId($resultData['transId']);
                $payment->setLastTransId($resultData['transId']);
                $payment->setAdditionalInformation('order_tra_id', $resultData['transId']);
                if (!empty($resultData['authorization'])) {
                    $payment->setAdditionalInformation('auth_code', $resultData['authorization']);
                }

                if (!empty($resultData['accountNumber'])) {
                    $payment->setCcLast4(substr($resultData['accountNumber'], -4));
                }
                if (!empty($resultData['accountType'])) {
                    $ccType = $this->customHelper->getCcTypeCodeByName($resultData['accountType']);
                    if ($ccType) {
                        $payment->setCcType($ccType);
                    }
                }
                $getTraData = $this->customApi->getTraDetail($resultData['transId']);
                $payment->setCcStatus($resultData['responseCode']);
                if (!empty($getTraData['transaction']['responseReasonDescription'])) {
                    $payment->setCcStatusDescription(substr($getTraData['transaction']['responseReasonDescription'], 0, 32));
                }

                if (!empty($getTraData['transaction']['AVSResponse'])) {
                    $payment->setCcAvsStatus($getTraData['transaction']['AVSResponse']);
                }
                if (!empty($getTraData['transaction']['cardCodeResponse'])) {
                    $payment->setCcCidStatus($getTraData['transaction']['cardCodeResponse']);
                }
                $payment->setIsTransactionClosed(0);
                $payment->setAdditionalInformation('payment_method_nonce', '');
            } else {
                throw new LocalizedException(__('There is an error in payment processing, please try again.'));
            }
        }
        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            throw new LocalizedException(__('Error: Invalid amount for capture.'));
        }

        if (isset($payment->getAdditionalInformation()['order_tra_id'])) {
            $trn_type = 'c';
            $ref_id = $payment->getAdditionalInformation()['order_tra_id'];
            $resultDataArray = $this->createCharge($payment, $amount, $trn_type, $ref_id);
            $resultData = $resultDataArray['transactionResponse'];
        } else {
            $posts = $payment->getData('additional_information');
            if (empty($posts['payment_method_nonce'])) {
                throw new LocalizedException(__('Error Processing the request. No response data found from the authorize.net server'));
            }
            $resultJsonData = $posts['payment_method_nonce'];
            $resultData = json_decode($resultJsonData, true);

            if ($this->customHelper->getConfig('payment/rootways_authorizecim_basic/debug')) {
                $reqLog = ["Request" => 'No request data. Only response data is available as this is a log data of iFrame response.', "Response" => $resultData];
                $this->rwLogger->debug($reqLog);
            }
        }

        if (!isset($resultData['transId'])) {
            $errorMsg = 'There is an error in payment processing.';
            if (isset($resultData['errors']['error']['errorText'])) {
                $errorMsg = $errorMsg . ' Error code and text from Authorize.net server is, Error Code: '.$resultData['errors']['error']['errorCode'].' Error Text: '.$resultData['errors']['error']['errorText'];
            }
            throw new LocalizedException(__($errorMsg));
        } else {
            if ($resultData['responseCode'] == '1' || $resultData['responseCode'] == '4') {
                $payment->setTransactionId($resultData['transId']);
                $payment->setCcTransId($resultData['transId']);
                $payment->setLastTransId($resultData['transId']);
                if (!empty($resultData['authorization'])) {
                    $payment->setAdditionalInformation('auth_code', $resultData['authorization']);
                }
                if (!isset($payment->getAdditionalInformation()['order_tra_id'])) {
                    if (!empty($resultData['accountNumber'])) {
                        $payment->setCcLast4(substr($resultData['accountNumber'], -4));
                    }
                    if (!empty($resultData['accountType'])) {
                        $ccType = $this->customHelper->getCcTypeCodeByName($resultData['accountType']);
                        if ($ccType) {
                            $payment->setCcType($ccType);
                        }
                    }
                    $getTraData = $this->customApi->getTraDetail($resultData['transId']);
                    $payment->setCcStatus($resultData['responseCode']);
                    if (!empty($getTraData['transaction']['responseReasonDescription'])) {
                        $payment->setCcStatusDescription(substr($getTraData['transaction']['responseReasonDescription'], 0, 32));
                    }

                    if (!empty($getTraData['transaction']['AVSResponse'])) {
                        $payment->setCcAvsStatus($getTraData['transaction']['AVSResponse']);
                    }
                    if (!empty($getTraData['transaction']['cardCodeResponse'])) {
                        $payment->setCcCidStatus($getTraData['transaction']['cardCodeResponse']);
                    }
                }
                $payment->setAdditionalInformation('order_tra_id', $resultData['transId']);
                $payment->setIsTransactionClosed(1);
                $payment->setAdditionalInformation('payment_method_nonce', '');
            } else {
                $errorMsg = 'There is an error in payment processing.';
                if (isset($resultData['errors']['error']['errorText'])) {
                    $errorMsg = $errorMsg . ' Error code and text from Authorize.net server is, Error Code: '.$resultData['errors']['error']['errorCode'].' Error Text: '.$resultData['errors']['error']['errorText'];
                }
                throw new LocalizedException(__($errorMsg));
            }
        }
        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (isset($payment->getAdditionalInformation()['order_tra_id']) && $amount > 0) {
            $amount = number_format($amount, 2, '.', '');
            $trn_type = 'r';
            $resultDataArray = $this->createCharge($payment, $amount, $trn_type, $payment->getAdditionalInformation()['order_tra_id']);
            $resultData = $resultDataArray['transactionResponse'];
            if ($resultData['responseCode'] == '1' || $resultData['responseCode'] == '4') {

            } else {
                $errorMsg = 'There is an error in refund processing.';
                if (isset($resultData['errors']['error']['errorText'])) {
                    $errorMsg = $errorMsg . ' Error code and text from Authorize.net server is, Error Code: '.$resultData['errors']['error']['errorCode'].' Error Text: '.$resultData['errors']['error']['errorText'];
                }
                throw new LocalizedException(__($errorMsg));
            }

        } else {
            throw new LocalizedException(__('Not able to find transaction ID for refund request.'));
        }
        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        $trn_type = 'v';
        if ($payment->getTransactionId()) {
            $amount = $payment->getAmountAuthorized();
            $resultDataArray = $this->createCharge($payment, $amount, $trn_type, $payment->getTransactionId());
            $resultData = $resultDataArray['transactionResponse'];
            if (!isset($resultData['transId'])) {
                $errorMsg = 'There is an error in void processing.';
                if (isset($resultData['errors']['error']['errorText'])) {
                    $errorMsg = $errorMsg . ' Error code and text from Authorize.net server is, Error Code: '.$resultData['errors']['error']['errorCode'].' Error Text: '.$resultData['errors']['error']['errorText'];
                }
                throw new LocalizedException(__($errorMsg));
            } else {
                if ($resultData['responseCode'] == '1' || $resultData['responseCode'] == '4') {
                    $payment->setAdditionalInformation('order_tra_id', $resultData['transId']);
                } else {
                    $errorMsg = 'There is an error in void processing.';
                    if (isset($resultData['errors']['error']['errorText'])) {
                        $errorMsg = $resultData['errors']['error']['errorCode'] == '16' ?
                            "The transaction cannot be found, or it is expired. If you are getting this error at the time of VOID, then use Cancel instead of VOID." :
                            $errorMsg . ' Error code and text from Authorize.net server is, Error Code: '.$resultData['errors']['error']['errorCode'].' Error Text: '.$resultData['errors']['error']['errorText'];
                    }
                    throw new LocalizedException(__($errorMsg));
                }
            }
        } else {
            throw new LocalizedException(__('Error in void the payment.'));
        }
        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     *
     * @return object
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $trn_type = 'v';
        if ($payment->getTransactionId()) {
            $amount = $payment->getAmountAuthorized();
            $resultDataArray = $this->createCharge($payment, $amount, $trn_type, $payment->getTransactionId());
            $resultData = $resultDataArray['transactionResponse'];
            if (!isset($resultData['transId'])) {
                $errorMsg = 'There is an error in void processing.';
                if (isset($resultData['errors']['error']['errorText'])) {
                    $errorMsg = $errorMsg . ' Error code and text from Authorize.net server is, Error Code: '.$resultData['errors']['error']['errorCode'].' Error Text: '.$resultData['errors']['error']['errorText'];
                }
                throw new LocalizedException(__($errorMsg));
            } else {
                if ($resultData['responseCode'] == '1' || $resultData['responseCode'] == '4') {
                    $payment->setAdditionalInformation('order_tra_id', $resultData['transId']);
                } else {
                    $getTraData = $this->customApi->getTraDetail(str_replace('-void', '', $payment->getTransactionId()));
                    if (!empty($getTraData['transaction']['transactionStatus']) &&
                        $getTraData['transaction']['transactionStatus'] == 'expired'
                    ) {
                        return $this;
                    } elseif (!empty($resultData['errors']['error']['errorCode']) &&
                        ($resultData['errors']['error']['errorCode'] == '16' || $resultData['errors']['error']['errorCode'] == '237')
                    ) {
                        return $this;
                    }

                    $errorMsg = 'There is an error in void processing.';
                    if (isset($resultData['errors']['error']['errorText'])) {
                        $errorMsg = $resultData['errors']['error']['errorCode'] == '16' ?
                            "The transaction cannot be found, or it is expired. If you are getting this error at the time of VOID, then use Cancel instead of VOID." :
                            $errorMsg . ' Error code and text from Authorize.net server is, Error Code: '.$resultData['errors']['error']['errorCode'].' Error Text: '.$resultData['errors']['error']['errorText'];
                    }
                    throw new LocalizedException(__($errorMsg));
                }
            }
        } else {
            throw new LocalizedException(__('Error in void the payment.'));
        }

        return $this;
    }

    private function createCharge($payment, $amount, $trn_type, $ref_id)
    {
        $xmlStr = '<?xml version="1.0" encoding="utf-8"?>
        <createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
            <merchantAuthentication></merchantAuthentication>
            <transactionRequest></transactionRequest>
        </createTransactionRequest>';

        $xml = simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOWARNING);
        // $xml = new SimpleXMLElement($xmlStr);

        $xml->merchantAuthentication->addChild('name', $this->customHelper->getApiLoginId());
        $xml->merchantAuthentication->addChild('transactionKey', $this->customHelper->getTransactionKey());

        if ($trn_type == 'v') {
            $tType = 'voidTransaction'; // Void
            $chargeId = str_replace('-void', '', $ref_id);
        } elseif ($trn_type == 'r') {
            $tType = 'refundTransaction'; // Refund
            $chargeId = str_replace('-refund', '', $ref_id);
        } else {
            $tType = 'priorAuthCaptureTransaction'; // Capture Pre-Auth
            $chargeId = str_replace('-capture', '', $ref_id);
        }

        $xml->transactionRequest->addChild('transactionType', $tType);
        if ($trn_type != 'v') {
            $xml->transactionRequest->addChild('amount', $amount);
        }
        if ($trn_type == 'r') {
            if (null === $payment->getCcLast4()) {
                throw new LocalizedException(__('Credit Card last 4 digits are missing, it require for refund request.'));
            }
            $ccLast4Digit = substr($payment->getCcLast4(), -4);
            $xml->transactionRequest->addChild('payment');
            $xml->transactionRequest->payment->addChild('creditCard');
            $xml->transactionRequest->payment->creditCard->addChild('cardNumber', $ccLast4Digit);
            $xml->transactionRequest->payment->creditCard->addChild('expirationDate', 'XXXX');
        }
        $xml->transactionRequest->addChild('refTransId', $chargeId);

        $gatewayUrl = $this->customHelper->getGatewayUrl();
        $VERIFYPEER = 'false';
        $VERIFYHOST = 'false';
        if ($merchantId = $this->customHelper->getEnvironment() == 'production') {
            $VERIFYPEER = 1;
            $VERIFYHOST = 2;
        }
        $resultData = '';
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gatewayUrl);
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
            if (false === $content) {
                //throw new \Exception(curl_error($ch), curl_errno($ch));
                throw new LocalizedException(__('No data found in the transaction response.'));
            }
            curl_close($ch);

            $xmlResult = simplexml_load_string($content);
            $resultData = json_encode($xmlResult);
            $resultData = json_decode($resultData, true);
        } catch (\Exception $e) {
            $resultData = '';
            //trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
            throw new LocalizedException(__('Curl failed with the error.'));
        }

        return $resultData;
    }

    /**
     * @param \Magento\Framework\DataObject $data
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $post = $data->getData()['additional_data'];
        if (array_key_exists('payment_method_nonce', $post) &&
            $post['payment_method_nonce'] != ''
           ) {
            $this->getInfoInstance()->setAdditionalInformation('payment_method_nonce', $post['payment_method_nonce']);
        } else {
            $this->getInfoInstance()->setAdditionalInformation('payment_method_nonce', '');
        }

        return $this;
    }

    function unsetValuesFromLogFile($data)
    {
        if (isset($data['pan'])) {
            unset($data['pan']);
        }
        if (isset($data['expdate'])) {
            unset($data['expdate']);
        }
        if (isset($data['txn_number'])) {
            unset($data['txn_number']);
        }
        return $data;
    }

    public function rwLogger($req, $res)
    {
        $reqRes = ["Request" => $req, "Response" => $res];
        $this->rwLogger->debug($reqRes);
    }
}
