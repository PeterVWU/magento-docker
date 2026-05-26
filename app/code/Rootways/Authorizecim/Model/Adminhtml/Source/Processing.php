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
namespace Rootways\Authorizecim\Model\Adminhtml\Source;

class Processing extends \Magento\Sales\Model\Config\Source\Order\Status
{
    protected $_stateStatuses = [\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, \Magento\Sales\Model\Order::STATE_PROCESSING, \Magento\Sales\Model\Order::STATE_COMPLETE, \Magento\Sales\Model\Order::STATE_CLOSED];
}
