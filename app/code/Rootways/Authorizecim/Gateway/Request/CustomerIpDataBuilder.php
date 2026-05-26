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
use Rootways\Authorizecim\Helper\Data;

/**
 * Class CustomerIpDataBuilder
 */
class CustomerIpDataBuilder implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * CustomerIpDataBuilder constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->customHelper = $helper;
    }

    /**
     * Build request by adding IP address of the customer.
     */
    public function build(array $buildSubject)
    {
        $result['transactionRequest'] = [
            'customerIP' => $this->customHelper->getCustomerIp(),
            'transactionSettings' => [
                'setting' => [
                    'settingName' => 'duplicateWindow',
                    'settingValue' => \Rootways\Authorizecim\Model\SampleConfigProvider::DUPLICATE_WINDOW
                ]
            ]
        ];

        return $result;
    }
}
