<?php
declare(strict_types=1);

namespace Vapewholesaleusa\GcsRemoteStorage\Test\Unit\Plugin\Cms\Wysiwyg\Images;

use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Vapewholesaleusa\GcsRemoteStorage\Plugin\Cms\Wysiwyg\Images\StoragePlugin;

class StoragePluginTest extends TestCase
{
    public function testNormalizesMissingSeparatorAfterStorageRoot(): void
    {
        $images = $this->createMock(Images::class);
        $images->method('getStorageRoot')->willReturn('/var/www/html/pub/media/');
        $storage = $this->createMock(Storage::class);
        $plugin = new StoragePlugin($images, new NullLogger(), $this->createStoreManager());

        [$filePath, $checkFile] = $plugin->beforeGetThumbnailPath(
            $storage,
            '/var/www/html/pub/media/catalog/category/banner.png',
            true
        );

        self::assertSame('/var/www/html/pub/media/catalog/category/banner.png', $filePath);
        self::assertTrue($checkFile);

        [$filePath] = $plugin->beforeGetThumbnailPath(
            $storage,
            '/var/www/html/pub/media' . 'catalog/category/banner.png',
            false
        );

        self::assertSame('/var/www/html/pub/media/catalog/category/banner.png', $filePath);
    }

    public function testFallsBackToOriginalMediaUrlWhenThumbnailUrlIsUnavailable(): void
    {
        $images = $this->createMock(Images::class);
        $images->method('getStorageRoot')->willReturn('/var/www/html/pub/media/');
        $storage = $this->createMock(Storage::class);
        $plugin = new StoragePlugin($images, new NullLogger(), $this->createStoreManager());

        $result = $plugin->afterGetThumbnailUrl(
            $storage,
            false,
            '/var/www/html/pub/media/catalog/category/banner.png'
        );

        self::assertSame('http://localhost:8080/media/catalog/category/banner.png', $result);
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
