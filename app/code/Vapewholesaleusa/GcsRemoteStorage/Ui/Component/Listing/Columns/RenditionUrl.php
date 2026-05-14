<?php
declare(strict_types=1);

namespace Vapewholesaleusa\GcsRemoteStorage\Ui\Component\Listing\Columns;

use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\MediaGalleryRenditionsApi\Api\GenerateRenditionsInterface;
use Magento\MediaGalleryRenditionsApi\Api\GetRenditionPathInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Psr\Log\LoggerInterface;

class RenditionUrl extends Column
{
    private const ACL_IMAGE_ACTIONS = [
        'image-details' => 'Magento_Cms::media_gallery',
        'insert' => 'Magento_MediaGalleryUiApi::insert_assets',
        'delete' => 'Magento_MediaGalleryUiApi::delete_assets',
        'edit' => 'Magento_MediaGalleryUiApi::edit_assets',
    ];

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly BackendUrlInterface $urlInterface,
        private readonly Images $images,
        private readonly AuthorizationInterface $authorization,
        private readonly GenerateRenditionsInterface $generateRenditions,
        private readonly GetRenditionPathInterface $getRenditionPath,
        private readonly LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $path = (string)$item['path'];
                $item['encoded_id'] = $this->images->idEncode($path);
                $item[$this->getData('name')] = $this->getUrl($path);
            }
        }

        return $dataSource;
    }

    public function prepare(): void
    {
        parent::prepare();
        $this->setData(
            'config',
            array_replace_recursive(
                (array)$this->getData('config'),
                [
                    'allowedActions' => $this->getAllowedActions(),
                    'onInsertUrl' => $this->urlInterface->getUrl('media_gallery/image/oninsert'),
                    'storeId' => $this->storeManager->getStore()->getId(),
                ]
            )
        );
    }

    private function getAllowedActions(): array
    {
        $allowedActions = [];
        foreach (self::ACL_IMAGE_ACTIONS as $key => $action) {
            if ($this->authorization->isAllowed($action)) {
                $allowedActions[] = $key;
            }
        }

        return $allowedActions;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function getUrl(string $path): string
    {
        try {
            $this->generateRenditions->execute([$path]);
            $path = $this->getRenditionPath->execute($path);
        } catch (\Throwable $exception) {
            $this->logger->debug(
                'Unable to generate media gallery rendition for listing thumbnail.',
                ['path' => $path, 'exception' => $exception]
            );
        }

        return rtrim($this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/')
            . '/'
            . ltrim($path, '/');
    }
}
