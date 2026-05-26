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
namespace Rootways\Authorizecim\Model\Method;

use Psr\Log\LoggerInterface;
use Rootways\Authorizecim\Helper\Data;

/**
 * Class Logger
 */
class Logger
{
    const DEBUG_KEYS_MASK = '****';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    private $customHelper;

    /**
     * @param LoggerInterface $logger
     * @param Data $customHelper
     */
    public function __construct(
        LoggerInterface $logger,
        Data $customHelper
    ) {
        $this->logger = $logger;
        $this->customHelper = $customHelper;
    }

    /**
     * Logs payment related information used for debug
     *
     * @param array $data
     * @param array|null $maskKeys
     * @param bool|null $forceDebug
     * @return void
     */
    public function debug(array $data, array $maskKeys = null, $forceDebug = null)
    {
        $maskKeys = $this->getDebugReplaceFields($maskKeys);
        $debugOn = $forceDebug !== null ?
            $forceDebug :
            (bool)$this->customHelper->getConfig('payment/rootways_authorizecim_basic/debug');
        if ($debugOn === true) {
            $data = $this->filterDebugData(
                $data,
                $maskKeys
            );
            $this->logger->debug(var_export($data, true));
        }
    }

    /**
     * Returns configured keys to be replaced with mask
     *
     * @return array
     */
    private function getDebugReplaceFields($maskKeys)
    {
        $globalReplaceFiled = ['store_id', 'api_token'];
        $maskKeys = $maskKeys !== null ? $maskKeys : [];
        $replaceFiled = array_merge($globalReplaceFiled, $maskKeys);

        return $replaceFiled;
    }

    /**
     * Recursive filter data by private conventions
     *
     * @param array $debugData
     * @param array $debugReplacePrivateDataKeys
     * @return array
     */
    protected function filterDebugData(array $debugData, array $debugReplacePrivateDataKeys)
    {
        $debugReplacePrivateDataKeys = array_map('strtolower', $debugReplacePrivateDataKeys);

        foreach (array_keys($debugData) as $key) {
            if (in_array(strtolower($key), $debugReplacePrivateDataKeys)) {
                $debugData[$key] = self::DEBUG_KEYS_MASK;
            } elseif (is_array($debugData[$key])) {
                $debugData[$key] = $this->filterDebugData($debugData[$key], $debugReplacePrivateDataKeys);
            }
        }
        return $debugData;
    }
}
