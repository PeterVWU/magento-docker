<?php

namespace Vapewholesaleusa\OrderSource\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Vapewholesaleusa\OrderSource\Model\OrderSourceFactory;
use Vapewholesaleusa\OrderSource\Services\GetAllSourcesAsOptionService;

class SourcesTab extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'Vapewholesaleusa_OrderSource::sources.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    private $_coreRegistry;

    /**
     * @var OrderSourceFactory
     */
    protected $orderSourceFactory;

    /**
     * @var GetAllSourcesAsOptionService
     */
    protected $getAllSourcesAsOptionService;

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param OrderSourceFactory $orderSourceFactory
     * @param GetAllSourcesAsOptionService $getAllSourcesAsOptionService
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry             $registry,
        OrderSourceFactory                      $orderSourceFactory,
        GetAllSourcesAsOptionService            $getAllSourcesAsOptionService,
        array                                   $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->orderSourceFactory = $orderSourceFactory;
        $this->getAllSourcesAsOptionService = $getAllSourcesAsOptionService;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @return array
     */
    public function allSources()
    {
        return $this->getAllSourcesAsOptionService->execute();
    }

    /**
     * @return mixed
     */
    public function getSources()
    {
        $order = $this->getOrder();
        $orderSource = $this->orderSourceFactory->create();
        $orderSourceCollection = $orderSource->getCollection()->addFieldToFilter('order_id', (int)$order->getEntityId());
        return $orderSourceCollection;
    }

    /**
     * @return array
     */
    public function sourcesPerSku()
    {
        $sources = $this->getSources();
        $sourcesPerSku = [];
        foreach ($sources as $source) {
            $sourcesPerSku[$source->getSku()][] = $source;
        }
        return $sourcesPerSku;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Sources Tab');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Sources Tab');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
