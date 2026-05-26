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
namespace Rootways\Authorizecim\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Rootways\Authorizecim\Observer\DataAssignObserver;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Rootways\Authorizecim\Model\Request\Api as CustomApi;

/**
 * Class VaultDataBuilder
 */
class VaultDataBuilder implements BuilderInterface
{
    /**
     *
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @var CustomApi
     */
    protected $customApi;

    /**
     * VaultDataBuilder constructor.
     * @param \Rootways\Authorizecim\Helper\Data $helper
     * @param CustomApi $customApi
     */
    public function __construct(
        \Rootways\Authorizecim\Helper\Data $helper,
        CustomApi $customApi
    ) {
        $this->customHelper = $helper;
        $this->customApi = $customApi;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $result = [];
        $saveCard = $payment->getAdditionalInformation(DataAssignObserver::SAVE_CARD);
        $createProfile = 'true';
        if ($saveCard == '1') {
            $result['transactionRequest'] = [
                'profile' => [
                    'createProfile' => $createProfile
                ]
            ];
        }

        return $result;
    }
}
