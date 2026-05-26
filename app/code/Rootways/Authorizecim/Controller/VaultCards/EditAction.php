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

use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Controller\CardsManagement;
use Magento\Vault\Model\PaymentTokenManagement;
use Rootways\Authorizecim\Helper\Data;
use Rootways\Authorizecim\Model\Request\Api;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EditAction extends CardsManagement
{
    const WRONG_REQUEST = 1;

    const WRONG_TOKEN = 2;

    const ACTION_EXCEPTION = 3;

    const AUTHORIZECIM_ERROR = 4;

    /**
     * @var array
     */
    protected $errorsMap = [];

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Validator
     */
    protected $fkValidator;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    protected $tokenRepository;

    /**
     * @var PaymentTokenManagement
     */
    protected $paymentTokenManagement;

    /**
     * @var Api
     */
    protected $rwApi;

    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * EditAction constructor.
     * @param Context $context
     * @param Session $customerSession
     * @param JsonFactory $jsonFactory
     * @param Validator $fkValidator
     * @param PaymentTokenRepositoryInterface $tokenRepository
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param Api $rwApi
     * @param Data $customHelper
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        JsonFactory $jsonFactory,
        Validator $fkValidator,
        PaymentTokenRepositoryInterface $tokenRepository,
        PaymentTokenManagement $paymentTokenManagement,
        Api $rwApi,
        Data $customHelper,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $customerSession);
        $this->jsonFactory = $jsonFactory;
        $this->fkValidator = $fkValidator;
        $this->tokenRepository = $tokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->rwApi = $rwApi;
        $this->customHelper = $customHelper;
        $this->resultPageFactory = $resultPageFactory;

        $this->errorsMap = [
            self::WRONG_TOKEN => __('No token found.'),
            self::WRONG_REQUEST => __('Wrong request.'),
            self::ACTION_EXCEPTION => __('Deletion failure. Please try again.'),
            self::AUTHORIZECIM_ERROR => __('Error while deleting this payment ID to Authorize.net server. Please try again.')
        ];
    }

    /**
     * Dispatch request
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $request = $this->_request;

        if (!$request instanceof Http) {
            return $this->createErrorResponse(self::WRONG_REQUEST);
        }

        if (!$this->fkValidator->validate($request)) {
            return $this->createErrorResponse(self::WRONG_REQUEST);
        }

        $paymentToken = $this->getPaymentToken($request);
        if ($paymentToken === null) {
            return $this->createErrorResponse(self::WRONG_TOKEN);
        }

        $resultPage = $this->resultPageFactory->create();

        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('vault/cards/listaction');
        }
        if ($paymentToken->getPaymentMethodCode() == 'rootways_authorizecim_option') {
            $ccDetails = $this->customHelper->getJsonDecode($paymentToken->getDetails());

            $getCustomerProfile = $this->rwApi->getCustomerPaymentProfile(
                $this->customHelper->formatedCustomerId($paymentToken->getGatewayToken()),
                $this->customHelper->getPaymentIdByToken($paymentToken)
            );
            $data = [
                'public_hash' => $request->getPostValue(PaymentTokenInterface::PUBLIC_HASH),
                'ccgt' => $this->customHelper->formatedCustomerId($paymentToken->getGatewayToken()),
                'ccpid' => $this->customHelper->getPaymentIdByToken($paymentToken),
                'cct' => $ccDetails['type'],
                'ccn' => $ccDetails['maskedCC'],
                'ccd' => $ccDetails['expirationDate'],
                'ccdetails' => $getCustomerProfile
            ];
            $resultPage->getLayout()->getBlock('rw-authorizecim-card-edit')->setTokenData($data);
        } else {
            $this->messageManager->addError('Card information missing.');
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/listing');
        }

        return $resultPage;
    }

    /**
     * @param int $errorCode
     * @return ResponseInterface
     */
    private function createErrorResponse($errorCode)
    {
        $this->messageManager->addErrorMessage(
            $this->errorsMap[$errorCode]
        );

        return $this->_redirect('vault/cards/listaction');
    }

    /**
     * @return ResponseInterface
     */
    private function createSuccessMessage()
    {
        $this->messageManager->addSuccessMessage(
            __('Stored Payment Method was successfully added')
        );
        return $this->_redirect('vault/cards/listaction');
    }

    /**
     * @param Http $request
     * @return PaymentTokenInterface|null
     */
    private function getPaymentToken(Http $request)
    {
        $publicHash = $request->getPostValue(PaymentTokenInterface::PUBLIC_HASH);

        if ($publicHash === null) {
            return null;
        }

        return $this->paymentTokenManagement->getByPublicHash(
            $publicHash,
            $this->customerSession->getCustomerId()
        );
    }
}
