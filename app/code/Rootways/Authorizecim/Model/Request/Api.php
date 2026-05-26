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
namespace Rootways\Authorizecim\Model\Request;

use Magento\Framework\Exception\LocalizedException;

class Api extends \Magento\Framework\Model\AbstractModel
{
    const RW_CUST_PRO_ID = 'rw_authorizecim_customer_profile_id';

    /**
     * Rootways Authorizecim helper.
     *
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $dbResource;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Rootways\Authorizecim\Helper\Data $customHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    ) {
        $this->customHelper = $customHelper;
        $this->dbResource = $resource;
    }

    /**
     * Common function for all operations.
     */
    protected function _sendRestRequest($request, $storeId = null)
    {
        $postUrl = $this->customHelper->getGatewayUrl($storeId);
        $header = ["POST /AUTHORIZE HTTP/1.0"];
        $header[] = "MIME-Version: 1.0";
        $header[] = "Content-type: text/xml";
        $header[] = "Content-length: ".strlen($request);
        $header[] = "Request-number: 1";
        $header[] = "Document-type: Request";
        $header[] = "Interface-Version: 0.3";
        $ch = curl_init($postUrl);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $result = curl_exec($ch);
        curl_close($ch);
        /*$new = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
        $con = json_encode($new);
        $newPaymentResponse = json_decode($con, true);*/
        $newPaymentResponse = $this->customHelper->validateXmlResponse($result);

        return $newPaymentResponse;
    }

    public function apiAuthentication()
    {
        $storeId = $this->customHelper->getAdminStoreId();
        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>
        <authenticateTestRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
            <merchantAuthentication>
                <name>'.$this->customHelper->getApiLoginId($storeId).'</name>
                <transactionKey>'.$this->customHelper->getTransactionKey($storeId).'</transactionKey>
            </merchantAuthentication>
        </authenticateTestRequest>';

        $restResponse = $this->_sendRestRequest($xmlData, $storeId);

        return $restResponse;
    }

    /**
     * Retrieve existing customer profile data.
     */
    public function getProfiles($pid)
    {
       /*
       <getCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
      <merchantAuthentication>
        <name>5KP3u95bQpv</name>
        <transactionKey>346HZ32z3fP4hTG2</transactionKey>
      </merchantAuthentication>
      <customerProfileId>10000</customerProfileId>
      <includeIssuerInfo>true</includeIssuerInfo>
    </getCustomerProfileRequest>
       */
    }


    /**
     * Create Profile Using Transaction.
     */
    public function createPaymentProfileFromTransactionRequest($transId, $custProId)
    {
        $storeId = $this->customHelper->getStoreId();
        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<createCustomerProfileFromTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
        $xmlData .= '<name>'.$this->customHelper->getApiLoginId($storeId).'</name>';
        $xmlData .= '<transactionKey>'.$this->customHelper->getTransactionKey($storeId).'</transactionKey>';
        $xmlData .= '</merchantAuthentication>';
        $xmlData .= '<transId>'.$transId.'</transId>';
        $xmlData .= '<customerProfileId>'.$custProId.'</customerProfileId>';
        $xmlData .= '</createCustomerProfileFromTransactionRequest>';
        $restResponse = $this->_sendRestRequest($xmlData);
        return $restResponse;
    }

    /**
     * Create Profile Using Transaction.
     */
    public function deleteProfilePaymentId($custProId, $custProPaymentId)
    {
        $xmlData = '<?xml version="1.0" encoding="utf-8"?>';
        $xmlData .= '<deleteCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
        $xmlData .= '<name>'.$this->customHelper->getApiLoginId().'</name>';
        $xmlData .= '<transactionKey>'.$this->customHelper->getTransactionKey().'</transactionKey>';
        $xmlData .= '</merchantAuthentication>';
        $xmlData .= '<customerProfileId>'.$custProId.'</customerProfileId>';
        $xmlData .= '<customerPaymentProfileId>'.$custProPaymentId.'</customerPaymentProfileId>';
        $xmlData .= '</deleteCustomerPaymentProfileRequest>';
        $restResponse = $this->_sendRestRequest($xmlData);
        $isDeleted = 0;
        if ($restResponse['messages']['resultCode'] == 'Ok' && $restResponse['messages']['message']['code'] == 'I00001') {
            $isDeleted = 1;
        }
        return $isDeleted;
    }

    /**
     * Transaction Caller
     */
    public function transactionCaller($amount, $callid, $dataValue, $dataKey)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlData .= '<createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
            $xmlData .= '<name>'.$this->customHelper->getApiLoginId().'</name>';
            $xmlData .= '<transactionKey>'.$this->customHelper->getTransactionKey().'</transactionKey>';
        $xmlData .= '</merchantAuthentication>';

        $xmlData .= '<transactionRequest>';
            $xmlData .= '<transactionType>'.$this->customHelper->getVisaCheckoutPaymentAction().'</transactionType>';
            $xmlData .= '<amount>'.$amount.'</amount>';
            $xmlData .= '<currencyCode>'.$this->customHelper->getVisaCheckoutCurrency().'</currencyCode>';


            $xmlData .= '<payment>';
                $xmlData .= '<opaqueData>';
                    $xmlData .= '<dataDescriptor>COMMON.VCO.ONLINE.PAYMENT</dataDescriptor>';
                    $xmlData .= '<dataValue>'.$dataValue.'</dataValue>';
                    $xmlData .= '<dataKey>'.$dataKey.'</dataKey>';
                $xmlData .= '</opaqueData>';
            $xmlData .= '</payment>';

            $xmlData .= '<callId>'.$callid.'</callId>';

        $xmlData .= '</transactionRequest>';
        $xmlData .= '</createTransactionRequest>';

        $restResponse = $this->_sendRestRequest($xmlData);

        return $restResponse;
    }

    public function getTraDetail($traId)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlData .= '<getTransactionDetailsRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
            $xmlData .= '<name>'.$this->customHelper->getApiLoginId().'</name>';
            $xmlData .= '<transactionKey>'.$this->customHelper->getTransactionKey().'</transactionKey>';
        $xmlData .= '</merchantAuthentication>';

        $xmlData .= '<transId>'.$traId.'</transId>';
        $xmlData .= '</getTransactionDetailsRequest>';

        $restResponse = $this->_sendRestRequest($xmlData);

        return $restResponse;
    }

    private function createCustomerProfileRequest($customerId, $customerEmail)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlData .= '<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
            $xmlData .= '<name>'.$this->customHelper->getApiLoginId().'</name>';
            $xmlData .= '<transactionKey>'.$this->customHelper->getTransactionKey().'</transactionKey>';
        $xmlData .= '</merchantAuthentication>';

        $xmlData .= '<profile>';
            $xmlData .= '<merchantCustomerId>' . $customerId . '</merchantCustomerId>';
            $xmlData .= '<email>' . $this->customHelper->subStrFun($customerEmail, 255) . '</email>';
        $xmlData .= '</profile>';

        $xmlData .= '</createCustomerProfileRequest>';

        $restResponse = $this->_sendRestRequest($xmlData);

        return $restResponse;
    }

    /**
     * Get Customer Profile ID created in the Authorize.net Account
     *
     * @return string.
     */
    public function getCustomerProfileId($email)
    {
        $customerProfileId = '';
        if ($this->customHelper->getCustomerId() != '') {
            $customerId = $this->customHelper->getCustomerId();
            $customerProfileId = $this->isCustomerProfileExist($customerId);
            if (!empty($customerProfileId)) {
                // Profile ID for the customer is already created.
            } else {
                // Create new profile ID for the customer and store it in the Database.
                $customerProfileId = $this->setCustomerProfileId($customerId, $email);
            }
        }

        return $customerProfileId;
    }

    public function getCustomerProfileIdForOrderOnlyPaymentAction($email)
    {
        $customerProfileId = '';
        if ($this->customHelper->getCustomerId() != '') {
            $customerId = $this->customHelper->getCustomerId();
            $customerProfileId = $this->isCustomerProfileExist($customerId);
            if (!empty($customerProfileId)) {
                // Profile ID for the customer is already created.
            } else {
                // Create new profile ID for the customer and store it in the Database.
                $customerProfileId = $this->setCustomerProfileId($customerId, $email);
            }
        } else {
            $response = $this->createCustomerProfileRequest(null, $email);
            if (isset($response['messages']['message']['code'])) {
                if ($response['messages']['message']['code'] == 'I00001') {
                    $customerProfileId = $response['customerProfileId'];
                } elseif ($response['messages']['message']['code'] == 'E00039') {
                    $customerProfileId = preg_replace('/[^0-9]/', '', $response['messages']['message']['text']);
                } else {
                    throw new LocalizedException(
                        __(__METHOD__ . " >> " . "{$response['messages']['resultCode']} {$response['messages']['message']['code']} {$response['messages']['message']['text']}")
                    );
                }
            }
        }

        return $customerProfileId;
    }

    /**
     * Check whether customer profile id exists or not in the Magento database
     * @param int $customerId
     * @return string
     */
    public function isCustomerProfileExist($customerId)
    {
        try {
            $readAdapter = $this->dbResource->getConnection('core_read');

            $attributeCode = self::RW_CUST_PRO_ID;
            $attributeTableName = $this->dbResource->getTableName('eav_attribute');
            $customerAttributeTableName = $this->dbResource->getTableName('customer_entity_varchar');

            $attributeIdSelect = $readAdapter->select()
                ->from($attributeTableName, 'attribute_id')
                ->where('attribute_code = ?', $attributeCode)
                ->limit(1);

            $attributeId = $attributeIdSelect->query()->fetchColumn();
            if ($attributeId == '') {
                return false;
            }

            $attributeValueSelect = $readAdapter->select()
                ->from($customerAttributeTableName, 'value')
                ->where('entity_id = ?', $customerId)
                ->where('attribute_id = ?', $attributeId)
                ->limit(1);

            $returnValue = $attributeValueSelect->query()->fetchColumn();
        } catch (\Exception $exception) {
            throw new LocalizedException(
                __(__METHOD__ . " >> " . $exception->getMessage())
            );
        }
        return $returnValue;
    }

    /**
     * Get Customer Profile ID created in the Authorize.net Account
     *
     * @return string.
     */
    public function setCustomerProfileId($customerId, $email)
    {
        $customerProfileId = '';

        $response = $this->createCustomerProfileRequest($customerId, $email);
        // Wee can add log here.
        if (isset($response['messages']['message']['code'])) {
            if ($response['messages']['message']['code'] == 'I00001') {
                $customerProfileId = $response['customerProfileId'];
            } elseif ($response['messages']['message']['code'] == 'E00039') {
                $customerProfileId = preg_replace('/[^0-9]/', '', $response['messages']['message']['text']);
            } else {
                throw new LocalizedException(
                    __(__METHOD__ . " >> " . "{$response['messages']['resultCode']} {$response['messages']['message']['code']} {$response['messages']['message']['text']}")
                );
            }
        }

        if (!empty($customerProfileId)) {
            $this->saveCustomerProfileId($customerId, $customerProfileId);
        }

        return $customerProfileId;
    }

    /**
     * Save Authorize.net customer profile id to the customer
     * @param int $customerId
     * @param int $customerProfileId
     * @return bool
     */
    public function saveCustomerProfileId($customerId, $customerProfileId)
    {
        try {
            $returnValue = false;

            $readAdapter = $this->dbResource->getConnection('core_read');
            $writeAdapter = $this->dbResource->getConnection('core_write');

            $attributeCode = self::RW_CUST_PRO_ID;
            $attributeTableName = $this->dbResource->getTableName('eav_attribute');
            $customerAttributeTableName = $this->dbResource->getTableName('customer_entity_varchar');

            $attributeIdSelect = $readAdapter->select()
                ->from($attributeTableName, 'attribute_id')
                ->where('attribute_code = ?', $attributeCode)
                ->limit(1);

            $attributeId = $attributeIdSelect->query()->fetchColumn();
            if ($attributeId == '') {
                return false;
            }

            $attributeValueIdSelect = $readAdapter->select()
                ->from($customerAttributeTableName, 'value_id')
                ->where('entity_id = ?', $customerId)
                ->where('attribute_id = ?', $attributeId)
                ->limit(1);

            $valueId = $attributeValueIdSelect->query()->fetchColumn();

            if ($valueId <= 0) {
                $saveQuery = $writeAdapter->insert(
                    $customerAttributeTableName,
                    [
                        'attribute_id' => $attributeId,
                        'entity_id' => $customerId,
                        'value' => $customerProfileId,
                        ]
                );
            } else {
                $saveQuery = $writeAdapter->update(
                    $customerAttributeTableName,
                    ['value' => $customerProfileId],
                    [
                        'value_id = ?' => $valueId,
                        'entity_id = ?' => $customerId,
                    ]
                );
            }

            $newValueId = $attributeValueIdSelect->query()->fetchColumn();

            $returnValue = ($newValueId <= 0);
        } catch (\Exception $exception) {
            throw new LocalizedException(
                __(__METHOD__ . " >> " . $exception->getMessage())
            );
            // We can add log here.
        }

        return $returnValue;
    }

    public function getCustomerPaymentProfile($customerProfileId, $customerPaymentProfileId)
    {

        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlData .= '<getCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
        $xmlData .= '<merchantAuthentication>';
        $xmlData .= '<name>'.$this->customHelper->getApiLoginId().'</name>';
        $xmlData .= '<transactionKey>'.$this->customHelper->getTransactionKey().'</transactionKey>';
        $xmlData .= '</merchantAuthentication>';

        $xmlData .= '<customerProfileId>'.$customerProfileId.'</customerProfileId>';
        $xmlData .= '<customerPaymentProfileId>'.$customerPaymentProfileId.'</customerPaymentProfileId>';
        $xmlData .= '<includeIssuerInfo>true</includeIssuerInfo>';
        $xmlData .= '</getCustomerPaymentProfileRequest>';

        return $this->_sendRestRequest($xmlData);
    }

    protected function dataConvertToXml($array, $rootElement = '<?xml version="1.0" encoding="utf-8"?><updateCustomerPaymentProfileRequest/>', $xml = null)
    {
        $_xml = $xml;

        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
            $_xml->addAttribute('xmlns', 'AnetApi/xml/v1/schema/AnetApiSchema.xsd');
        }

        // Visit all key value pair
        foreach ($array as $k => $v) {
            // If there is nested array then
            if (is_array($v)) {
                $this->dataConvertToXml($v, $k, $_xml->addChild($k));
            } else {
                $v = !empty($v) ? htmlspecialchars($v) : $v;
                // Simply add child element.
                $_xml->addChild($k, $v);
            }
        }

        return $_xml->asXML();
    }

    public function updateCustomerPaymentProfile($customerData)
    {
        $data['merchantAuthentication'] = [
            'name' => $this->customHelper->getApiLoginId(),
            'transactionKey'   => $this->customHelper->getTransactionKey()
        ];
        $data['customerProfileId'] = $customerData['paymentProfile']['customerProfileId'];
        $data['paymentProfile']['billTo'] = $customerData['paymentProfile']['billTo'];
        $data['paymentProfile']['payment'] = $customerData['paymentProfile']['payment'];
        $data['paymentProfile']['defaultPaymentProfile'] = 0;
        $data['paymentProfile']['customerPaymentProfileId'] = $customerData['paymentProfile']['customerPaymentProfileId'];
        $validationMode = $this->customHelper->getConfig('payment/rootways_authorizecim_option/validate_card');
        if ($validationMode != 'none') {
            $data['validationMode'] = $validationMode;
        }

        $xmlData = $this->dataConvertToXml($data);
        $result = $this->_sendRestRequest($xmlData);
        return $result;
    }
}
