<?php

namespace Vapewholesaleusa\AheadworksRewardPoints\Controller\Block;

use Aheadworks\RewardPoints\Controller\Block\Render as AheadworksRender;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Translate\InlineInterface;

class Render extends AheadworksRender
{
    /**
     * @var InlineInterface
     */
    private $translateInline;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var array
     */
    private $renders;

    /**
     * Render constructor.
     * @param Context $context
     * @param InlineInterface $translateInline
     * @param ProductRepositoryInterface $productRepository
     * @param array $renders
     */
    public function __construct(
        Context $context,
        InlineInterface $translateInline,
        ProductRepositoryInterface $productRepository,
        array $renders = []
    ) {
        parent::__construct(
            $context,
            $translateInline,
            $productRepository,
            $renders
        );
        $this->translateInline = $translateInline;
        $this->productRepository = $productRepository;
        $this->renders = $renders;
    }

    /**
     * Returns block content depends on ajax request
     *
     * @return \Magento\Framework\Controller\Result\Redirect|void
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setRefererOrBaseUrl();
        }
        $currentRoute = $this->getRequest()->getRouteName();
        $currentControllerName = $this->getRequest()->getControllerName();
        $currentActionName = $this->getRequest()->getActionName();
        $currentRequestUri = $this->getRequest()->getRequestUri();

        $origRequest = $this->getRequest()->getParam('originalRequest');
        if ($origRequest && is_string($origRequest)) {
            $origRequest = json_decode($origRequest, true);
        }

        if ($origRequest) {
            $this->getRequest()->setRouteName($origRequest['route']);
            $this->getRequest()->setControllerName($origRequest['controller']);
            $this->getRequest()->setActionName($origRequest['action']);
            $this->getRequest()->setRequestUri($origRequest['uri']);
        }

        $blocks = $this->getRequest()->getParam('blocks');
        $data = $this->getBlocks($blocks);

        $this->getRequest()->setRouteName($currentRoute);
        $this->getRequest()->setControllerName($currentControllerName);
        $this->getRequest()->setActionName($currentActionName);
        $this->getRequest()->setRequestUri($currentRequestUri);

        $this->translateInline->processResponseBody($data);
        $this->getResponse()->appendBody(json_encode($data));
    }

    /**
     * Get blocks from layout
     *
     * @param string $blocks
     * @return string[]
     */
    private function getBlocks($blocks)
    {
        if (!$blocks) {
            return [];
        }
        $blocks = json_decode($blocks);

        $data = [];
        $layout = $this->_view->getLayout();
        foreach ($blocks as $key => $blockName) {
            if (isset($this->renders[$blockName])) {
                $blockInstance = $layout->createBlock($this->renders[$blockName]);
                if (is_object($blockInstance)) {
                    $blockInstance->setNameInLayout($blockName . '_' . $key);
                    $data[$blockName] = $blockInstance->toHtml();
                }
            }
        }
        return $data;
    }
}
