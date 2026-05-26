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
 * Class TransferFactory
 */
class TransferFactory extends AbstractTransferFactory
{
    public function create(array $request)
    {
        $orderStoreId = null;
        if (isset($request['storeid'])) {
            $orderStoreId = $request['storeid'];
        }
        $getUrl = $this->getUrl($orderStoreId);
        unset($request['storeid']);

        $xmlData = $this->convertToXml($request);
        return $this->transferBuilder
            ->setMethod('POST')
            ->setHeaders(['Content-type' => 'Content-Type: text/xml'])
            ->setBody($xmlData)
            ->setUri($getUrl)
            ->build();
    }
}
