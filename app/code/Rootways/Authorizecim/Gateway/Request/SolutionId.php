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
 * Class SolutionId
 */
class SolutionId implements BuilderInterface
{
    /**
     * @var Data
     */
    protected $customHelper;

    /**
     * SolutionId constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->customHelper = $helper;
    }

    public function build(array $buildSubject)
    {
        $result['transactionRequest'] = [
            'solution' => [
                'id' => $this->customHelper->getAuthorizenetSolutionID()
            ]
        ];

        return $result;
    }
}
