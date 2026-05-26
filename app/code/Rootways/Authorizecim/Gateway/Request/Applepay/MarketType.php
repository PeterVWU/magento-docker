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
namespace Rootways\Authorizecim\Gateway\Request\Applepay;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * MarketType
 */
class MarketType implements BuilderInterface
{
    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @param \Rootways\Authorizecim\Helper\Data $helper
     */
    public function __construct(
        \Rootways\Authorizecim\Helper\Data $helper
    ) {
        $this->customHelper = $helper;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $result['transactionRequest']['retail'] = [
            'marketType' => 0,
            'deviceType' =>  8
        ];

        return $result;
    }
}
