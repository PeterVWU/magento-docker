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
namespace Rootways\Authorizecim\Gateway\Http;

/**
 * Class OrderOnlyVaultTransferFactory
 */
class OrderOnlyVaultTransferFactory extends OrderOnlyTransferFactory
{
    public function getRootElement()
    {
        return '<?xml version="1.0" encoding="utf-8"?><validateCustomerPaymentProfileRequest/>';
    }
}
