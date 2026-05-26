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
namespace Rootways\Authorizecim\Gateway\Http\Client;

use Laminas\Http\Exception\RuntimeException;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Rootways\Authorizecim\Model\Method\Logger as RwLogger;

/**
 * Class Zend
 */
class Zend extends \Magento\Payment\Gateway\Http\Client\Zend
{

    /**
     * @var ConverterInterface | null
     */
    private $converter;

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    private $customHelper;

    /**
     * @var RwLogger
     */
    private $rwLogger;

    public function __construct(
        ConverterInterface $converter = null,
        \Rootways\Authorizecim\Helper\Data $customHelper,
        RwLogger $rwLogger
    ) {
        $this->converter = $converter;
        $this->customHelper = $customHelper;
        $this->rwLogger = $rwLogger;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $resultNew = [];

        /** @var \Laminas\Http\Client $client */
        $client = new \Laminas\Http\Client();
        $client->setOptions(['timeout' => 45]);
        $client->setMethod($transferObject->getMethod());
        $client->setRawBody($transferObject->getBody());
        $client->setHeaders($transferObject->getHeaders());
        $client->setUri($transferObject->getUri());

        try {
            $response = $client->send();
            /*$new = simplexml_load_string($response->getBody(), 'SimpleXMLElement', LIBXML_NOWARNING);
            $con = json_encode($new);
            $resultNew = json_decode($con, true);*/
            $resultNew = $this->customHelper->validateXmlResponse($response->getBody());
        } catch (RuntimeException $e) {
            throw new ClientException(__($e->getMessage()));
        } catch (ConverterException $e) {
            throw $e;
        } finally {
            if ($this->customHelper->getConfig('payment/rootways_authorizecim_basic/debug')) {
                $reqOut = $this->delete_all_between('<merchantAuthentication>', '</merchantAuthentication>', $transferObject->getBody());
                $reqOut = $this->delete_all_between('<payment>', '</payment>', $reqOut);
                $resOut = $resultNew;
                $this->rwLogger($reqOut, $resOut);
            }
        }

        return $resultNew;
    }

    public function delete_all_between($beginning, $end, $string)
    {
        $beginningPos = strpos($string, $beginning);
        $endPos = strpos($string, $end);
        if ($beginningPos === false || $endPos === false) {
            return $string;
        }

        $textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

        return $this->delete_all_between($beginning, $end, str_replace($textToDelete, '', $string)); // recursion to ensure all occurrences are replaced
    }

    public function rwLogger($req, $res)
    {
        $reqRes = ["Request" => $req, "Response" => $res];
        $this->rwLogger->debug($reqRes);
    }
}
