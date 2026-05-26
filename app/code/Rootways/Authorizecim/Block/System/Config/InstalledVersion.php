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
namespace Rootways\Authorizecim\Block\System\Config;

use \Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class InstalledVersion
 */
class InstalledVersion extends Field
{
    /** @var Dir */
    protected $moduleDir;

    /** @var File */
    protected $fileHandler;

    /** @var Json */
    protected $_json;

    /**
     * InstalledVersion constructor.
     * @param Context $context
     * @param Dir $moduleDir
     * @param File $fileHandler
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        Dir $moduleDir,
        File $fileHandler,
        Json $json,
        array $data = []
    ) {
        $this->moduleDir = $moduleDir;
        $this->fileHandler = $fileHandler;
        $this->_json = $json;
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $composerFile = $this->fileHandler->read(
            $this->moduleDir->getDir('Rootways_Authorizecim') . '/composer.json'
        );
        $composer =  $this->_json->unserialize($composerFile);

        $title = !empty($composer['version']) ? $composer['version'] : __('Unknown');
        $html = '';
        $html .= <<<HTML
        <div style="background: #fff7cf;padding: 16px 15px 15px 35px;"><ul>
        <li style="padding: 3px 0 3px 0;">
            Installed Version: $title |
            <a href="https://www.rootways.com/magento-2-authorize-net-cim-extension#doctab" target="_blank">Check Available Latest Version</a> |
            <a target="_blank" href="https://www.rootways.com/magento-2-authorize-net-cim-extension#logs">Change Log</a>
        </li>
        <li style="padding: 3px 0 3px 0;"><a target="_blank" href="https://www.rootways.com/magento-2-authorize-net-cim-extension">Extension Page</a></li>
        <li style="padding: 3px 0 3px 0;"><a target="_blank" href="https://www.rootways.com/support">Technical Support</a></li>
        <li style="padding: 3px 0 3px 0;"><a target="_blank" href="https://www.rootways.com/magento-extension-customization">Extension Customization</a></li>
        <li style="padding: 3px 0 3px 0;">For any general enquiry, email us at <a target="_blank" href="mailto:help@rootways.com">help@rootways.com</a></li>
        <li style="padding: 3px 0 3px 0;"><a target="_blank" href="https://rootways.freshdesk.com/">Ticket System</a> (Note: Ticket system and rootways.com passwords are different.)</li>
        </ul></div>
HTML;
        return $html;
    }
}
