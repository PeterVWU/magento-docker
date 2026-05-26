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
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Controller\CardsManagement;
use Magento\Vault\Model\PaymentTokenManagement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeleteAction extends CardsManagement
{
    const WRONG_REQUEST = 1;

    const WRONG_TOKEN = 2;

    const ACTION_EXCEPTION = 3;

    const AUTHORIZECIM_ERROR = 4;

    /**
     * @var array
     */
    private $errorsMap = [];

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Validator
     */
    private $fkValidator;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $tokenRepository;

    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var \Rootways\Authorizecim\Model\Request\Api
     */
    private $rwApi;

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    private $customHelper;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param JsonFactory $jsonFactory
     * @param Validator $fkValidator
     * @param PaymentTokenRepositoryInterface $tokenRepository
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param \Rootways\Authorizecim\Model\Request\Api $rwApi
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        JsonFactory $jsonFactory,
        Validator $fkValidator,
        PaymentTokenRepositoryInterface $tokenRepository,
        PaymentTokenManagement $paymentTokenManagement,
        \Rootways\Authorizecim\Model\Request\Api $rwApi,
        \Rootways\Authorizecim\Helper\Data $customHelper
    ) {
        parent::__construct($context, $customerSession);
        $this->jsonFactory = $jsonFactory;
        $this->fkValidator = $fkValidator;
        $this->tokenRepository = $tokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->rwApi = $rwApi;
        $this->customHelper = $customHelper;

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

        /* Custom For Authorize.net Vault */
        if ($paymentToken->getPaymentMethodCode() == 'rootways_authorizecim_option') {
            $custProId = $this->customHelper->formatedCustomerId($paymentToken->getGatewayToken());
            $tokenDetails = json_decode($paymentToken->getTokenDetails(), true);
            $paymentId = '';
            if (isset($tokenDetails['paymentProfileId'])) {
                $paymentId = $tokenDetails['paymentProfileId'];
            }

            $isDeleted = $this->rwApi->deleteProfilePaymentId($custProId, $paymentId);
            if ($isDeleted == 0) {
                return $this->createErrorResponse(self::AUTHORIZECIM_ERROR);
            }
        }

        try {
            $this->tokenRepository->delete($paymentToken);
        } catch (\Exception $e) {
            return $this->createErrorResponse(self::ACTION_EXCEPTION);
        }

        return $this->createSuccessMessage();
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
            __('Stored Payment Method was successfully removed')
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
