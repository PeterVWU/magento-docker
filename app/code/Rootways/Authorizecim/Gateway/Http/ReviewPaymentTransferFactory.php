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
 * Class ReviewPaymentTransferFactory
 */
class ReviewPaymentTransferFactory extends AbstractTransferFactory
{
    public function create(array $request)
    {
        $orderStoreId = null;
        if (isset($request['storeid'])) {
            $orderStoreId = $request['storeid'];
        }
        $getUrl = $this->getUrl($orderStoreId);
        unset($request['storeid']);

        $rootElement = '<?xml version="1.0" encoding="utf-8"?><updateHeldTransactionRequest/>';
        $xmlData = $this->convertToXml($request, $rootElement);
        return $this->transferBuilder
            ->setMethod('POST')
            ->setHeaders(['Content-type' => 'Content-Type: text/xml'])
            ->setBody($xmlData)
            ->setUri($getUrl)
            ->build();
    }
}
