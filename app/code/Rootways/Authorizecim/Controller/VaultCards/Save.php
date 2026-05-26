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
namespace Rootways\Authorizecim\Controller\VaultCards;

use Magento\Framework\App\Action\Context;
use Rootways\Authorizecim\Helper\Data;
use Rootways\Authorizecim\Model\Request\Api;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var Api
     */
    protected $rwApi;

    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * Save constructor.
     * @param Context $context
     * @param Api $rwApi
     * @param Data $customHelper
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param Json|null $serializer
     */
    public function __construct(
        Context $context,
        Api $rwApi,
        Data $customHelper,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        Json $serializer = null
    ) {
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->rwApi = $rwApi;
        $this->customHelper = $customHelper;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        parent::__construct($context);
    }

    protected function validatePaymentProfileId(array $response)
    {
        return isset($response['customerProfileId'])
            && $response['customerProfileId'] != '' &&
            isset($response['customerPaymentProfileId'])
            && $response['customerPaymentProfileId'] != '';
    }

    protected function validationDirectResponse(array $response)
    {
        return !empty($response['messages']['message']['code']) &&
        $response['messages']['message']['code'] == 'I00001' ? true : false;
    }

    protected function getVaultPaymentToken($response)
    {
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD)->load($response['public_hash'], 'public_hash');
        if (!$paymentToken->getEntityId()) {
            return false;
        }

        $ccLast4 = substr($response['number'], -4);
        $ccType = $response['card_type'];
        $expMon = sprintf("%02d", $response['expiry_month']);
        $expYr = $response['expiry_year'];
        $expirationDate = $expMon . '/' . $expYr;
        $expAt = date('Y-m-d h:i:s', strtotime($expYr . '-' . $expMon . '-01 00:00:00'));
        $paymentToken->setExpiresAt($expAt);
        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $ccType,
            'maskedCC' => $ccLast4,
            'expirationDate' => $expirationDate,
            'paymentProfileId' => $response['ccpid']
        ]));

        try {
            $paymentToken->save();
        } catch (\Exception $e) {
            //return $this->createErrorResponse(self::ACTION_EXCEPTION);
            return false;
        }
        return true;
    }

    private function convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
    }

    public function execute()
    {
        $request = $this->_request;
        $resultRedirect = $this->resultRedirectFactory->create();
        $params = $request->getPostValue();
        if (!$params && empty($params['ccgt']) && empty($params['ccpid'])) {
            return $resultRedirect->setPath('vault/cards/listaction/');
        }

        $custProId = $params['ccgt'];
        $paymentId = $params['ccpid'];
        $getCustomerProfile = $this->rwApi->getCustomerPaymentProfile($custProId, $paymentId);
        if (empty($getCustomerProfile['paymentProfile']['payment'])) {
            $this->messageManager->addErrorMessage('Not able to find this token data from the Authorize.net server.');
            return $resultRedirect->setPath('vault/cards/listaction/');
        }

        if (!empty($params['firstName'])) {
            $getCustomerProfile['paymentProfile']['billTo']['firstName'] = $params['firstName'];
        }
        if (!empty($params['lastName'])) {
            $getCustomerProfile['paymentProfile']['billTo']['lastName'] = $params['lastName'];
        }
        if (!empty($params['address'])) {
            $getCustomerProfile['paymentProfile']['billTo']['address'] = $params['address'];
        }
        if (!empty($params['city'])) {
            $getCustomerProfile['paymentProfile']['billTo']['city'] = $params['city'];
        }
        if (!empty($params['region_id'])) {
            $state = $this->customHelper->getRegionCode($params['region_id']);
            $getCustomerProfile['paymentProfile']['billTo']['state'] = $state;
        } elseif (!empty($params['region'])) {
            $getCustomerProfile['paymentProfile']['billTo']['state'] = $params['region'];
        }
        if (!empty($params['country_id'])) {
            $getCustomerProfile['paymentProfile']['billTo']['country'] = $params['country_id'];
        }
        if (!empty($params['postcode'])) {
            $getCustomerProfile['paymentProfile']['billTo']['zip'] = $params['postcode'];
        }
        if (!empty($params['phone_number'])) {
            $getCustomerProfile['paymentProfile']['billTo']['phoneNumber'] = $params['phone_number'];
        }

        $getCustomerProfile['paymentProfile']['payment']['creditCard']['cardNumber'] = $params['number'];
        $expDate = $params['expiry_year'] . '-' . sprintf("%02d", $params['expiry_month']);
        $getCustomerProfile['paymentProfile']['payment']['creditCard']['expirationDate'] = $expDate;
        unset($getCustomerProfile['paymentProfile']['payment']['creditCard']['cardType']);
        unset($getCustomerProfile['paymentProfile']['payment']['creditCard']['issuerNumber']);

        $updateCustomerProfile = $this->rwApi->updateCustomerPaymentProfile($getCustomerProfile);
        $isApproved = $this->validationDirectResponse($updateCustomerProfile);
        if ($isApproved) {
            // update or add card to the Magento Vault.
            // TODO does it create issue when we save card with order and update here? Becuase that saved card data might be saved with that order extensionAttributes too.
            $isVaultUpdate = $this->getVaultPaymentToken($params);
            if ($isVaultUpdate) {
                 $this->messageManager->addSuccessMessage(__('Profile updated successfully.'));
            } else {
                $errorMsg = __('Card updated in the Payment Server bus issue when saving in your account. Please get in touch with us to get more details.');
                $this->messageManager->addSuccessMessage($errorMsg);
            }
        } else {
            $errorMsg = 'Issue in saving customer credit card. Make sure entered correct data.';
            if (!empty($updateCustomerProfile['messages']['message']['text'])) {
                 $errorMsg = 'Issue in saving customer credit card. Error: '.$updateCustomerProfile['messages']['message']['text'];
            }
            $this->messageManager->addErrorMessage(__($errorMsg));
        }
        return $resultRedirect->setPath('vault/cards/listaction/');
    }
}
