<?php

namespace Vapewholesaleusa\OrderSource\Plugin;

use Magento\Backend\Block\Widget\Form;
use Magento\Framework\Registry;
use Magento\Integration\Controller\Adminhtml\Integration;
use Vapewholesaleusa\OrderSource\Model\Helper\Data;
use Vapewholesaleusa\OrderSource\Services\GetAllSourcesAsOptionService;

/**
 * Class SourceFieldOnIntegration
 */
class SourceFieldOnIntegration
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var GetAllSourcesAsOptionService
     */
    protected $getAllSourcesAsOptionService;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * SourceFieldOnIntegration constructor.
     * @param Registry $registry
     * @param GetAllSourcesAsOptionService $getAllSourcesAsOptionService
     * @param Data $helper
     */
    public function __construct(
        Registry $registry,
        GetAllSourcesAsOptionService $getAllSourcesAsOptionService,
        Data $helper
    ) {
        $this->registry = $registry;
        $this->getAllSourcesAsOptionService = $getAllSourcesAsOptionService;
        $this->helper = $helper;
    }

    /**
     * @param Form $subject
     * @param $form
     * @return array
     */
    public function beforeSetForm(Form $subject, $form)
    {
        if(!$this->helper->isModuleEnabled()) {
            return [$form];
        }

        if(!$form->getElement('base_fieldset') || $form->getElement('source_code')) {
            return [$form];
        }

        $fieldset = $form->getElement('base_fieldset');
        $integrationData = $this->registry->registry(Integration::REGISTRY_KEY_CURRENT_INTEGRATION);
        $fieldset->addField(
            'source_code',
            'select',
            [
                'label' => __('Source'),
                'name' => 'source_code',
                'disabled' => false,
                'note' => 'Assign a source to limit the operations for one source.',
                'maxlength' => '254',
                'options' => $this->getAllSourcesAsOptionService->execute(),
                'value' => $integrationData['source_code'] ?? ''
            ]
        );

        return [$form];
    }
}
