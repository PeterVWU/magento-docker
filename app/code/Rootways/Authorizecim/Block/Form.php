<?php
/**
 * Authorizecim Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Block;

use Magento\Payment\Block\Form\Cc;

class Form extends Cc
{
    /**
     * @var string
     */
    protected $_template = 'Rootways_Authorizecim::form/admin_payments.phtml';

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customhelper;

    /**
     * Form constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Rootways\Authorizecim\Helper\Data $customhelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Rootways\Authorizecim\Helper\Data $customhelper,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->customhelper = $customhelper;
    }

    /**
     * @return bool
     */
    public function isVaultEnable()
    {
        return (bool) $this->customhelper->vaultEnable();
    }
}
