<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CmindsSalesrep\Ui\Component\MassAction;

class AssignSalesRep extends \Magento\Ui\Component\Action
{
    /**
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Data\OptionSourceInterface|null $optionSource
     * @param array $components
     * @param array $data
     * @param $actions
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        private readonly \Magento\Framework\UrlInterface             $urlBuilder,
        private readonly \Cminds\Salesrep\Model\Source\UsersList     $optionSource,
        array                                                        $components = [],
        array                                                        $data = [],
        $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);
    }

    /**
     * Complete Mass actions with external options
     */
    public function prepare()
    {
        $options = $this->optionSource->toOptionArray();
        foreach ($options as $option) {
            $this->actions[] = [
                'type'    => strtolower($option['label']),
                'label'   => $option['label'],
                'url'     => $this->urlBuilder->getUrl(
                    $this->_data['config']['massActionUrl'],
                    $this->getUrlParams($option['value'])
                ),
                'confirm' => $this->_data['config']['confirm']
            ];
        }
        parent::prepare();
    }

    /**
     * Prepare params array for urlBuilder
     *
     * @param int|string $optionValue
     *
     * @return array
     */
    private function getUrlParams(int|string $optionValue): array
    {
        $queryParams = [
            'salesrep_id' => $optionValue,
        ];

        return [
            '_secure' => true,
            '_query'  => $queryParams
        ];
    }
}
