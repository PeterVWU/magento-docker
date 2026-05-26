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

class EcheckForm extends \Magento\Payment\Block\Form
{
    /**
     * @var string
     */
    protected $_template = 'Rootways_Authorizecim::form/admin_echeck.phtml';

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customhelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Rootways\Authorizecim\Helper\Data $customhelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Rootways\Authorizecim\Helper\Data $customhelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customhelper = $customhelper;
    }

    /**
     * @return array
     */
    public function eCheckAccountTypes()
    {
        return $this->customhelper->getAchAccountTypes();
    }
}
