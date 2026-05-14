<?php
declare(strict_types=1);

namespace Vapewholesaleusa\GcsRemoteStorage\Test\Unit\Ui\Component\Listing\Columns;

use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\MediaGalleryRenditionsApi\Api\GenerateRenditionsInterface;
use Magento\MediaGalleryRenditionsApi\Api\GetRenditionPathInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Vapewholesaleusa\GcsRemoteStorage\Ui\Component\Listing\Columns\RenditionUrl;

class RenditionUrlTest extends TestCase
{
    public function testPrepareDataSourceUsesMediaGalleryRenditionUrl(): void
    {
        $images = $this->createMock(Images::class);
        $images->method('idEncode')->with('catalog/category/banner.png')->willReturn('encoded-path');
        $generateRenditions = $this->createMock(GenerateRenditionsInterface::class);
        $generateRenditions->expects(self::once())->method('execute')->with(['catalog/category/banner.png']);
        $getRenditionPath = $this->createMock(GetRenditionPathInterface::class);
        $getRenditionPath->method('execute')
            ->with('catalog/category/banner.png')
            ->willReturn('.renditions/catalog/category/banner.png');

        $column = new RenditionUrl(
            $this->createMock(ContextInterface::class),
            $this->createMock(UiComponentFactory::class),
            $this->createStoreManager(),
            $this->createMock(BackendUrlInterface::class),
            $images,
            $this->createMock(AuthorizationInterface::class),
            $generateRenditions,
            $getRenditionPath,
            new NullLogger(),
            [],
            ['name' => 'thumbnail_url']
        );

        $dataSource = [
            'data' => [
                'items' => [
                    [
                        'path' => 'catalog/category/banner.png',
                        'thumbnail_url' => 'catalog/category/banner.png',
                    ],
                ],
            ],
        ];

        $result = $column->prepareDataSource($dataSource);

        self::assertSame('encoded-path', $result['data']['items'][0]['encoded_id']);
        self::assertSame(
            'http://localhost:8080/media/.renditions/catalog/category/banner.png',
            $result['data']['items'][0]['thumbnail_url']
        );
    }

    private function createStoreManager(): StoreManagerInterface
    {
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->with(UrlInterface::URL_TYPE_MEDIA)->willReturn('http://localhost:8080/media/');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return $storeManager;
    }
}
