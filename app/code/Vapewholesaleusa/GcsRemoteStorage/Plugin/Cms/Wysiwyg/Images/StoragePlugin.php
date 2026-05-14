<?php
declare(strict_types=1);

namespace Vapewholesaleusa\GcsRemoteStorage\Plugin\Cms\Wysiwyg\Images;

use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Cms\Model\Wysiwyg\Images\Storage;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class StoragePlugin
{
    public function __construct(
        private readonly Images $images,
        private readonly LoggerInterface $logger,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Media Gallery can pass "media-root" . "relative/path" without a separator.
     */
    public function beforeGetThumbnailPath(Storage $subject, $filePath, $checkFile = false): array
    {
        if (is_string($filePath)) {
            $filePath = $this->normalizeFilePath($filePath);
        }

        return [$filePath, $checkFile];
    }

    public function beforeGetThumbnailUrl(Storage $subject, $filePath, $checkFile = false): array
    {
        if (is_string($filePath)) {
            $filePath = $this->normalizeFilePath($filePath);
            $this->ensureThumbnailExists($subject, $filePath);
        }

        return [$filePath, $checkFile];
    }

    public function beforeResizeFile(Storage $subject, $source, $keepRatio = true): array
    {
        if (is_string($source)) {
            $source = $this->normalizeFilePath($source);
        }

        return [$source, $keepRatio];
    }

    public function afterGetThumbnailUrl(Storage $subject, string|false $result, $filePath, $checkFile = false): string|false
    {
        if ($result || !is_string($filePath)) {
            return $result;
        }

        $filePath = $this->normalizeFilePath($filePath);
        $relativePath = $this->getRelativeMediaPath($filePath);

        if ($relativePath === null) {
            return false;
        }

        return rtrim($this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/')
            . '/'
            . ltrim($relativePath, '/');
    }

    private function ensureThumbnailExists(Storage $subject, string $filePath): void
    {
        try {
            if (!$subject->getThumbnailPath($filePath, true)) {
                $subject->resizeFile($filePath);
            }
        } catch (\Throwable $exception) {
            $this->logger->debug(
                'Unable to create media gallery thumbnail on demand.',
                ['exception' => $exception]
            );
        }
    }

    private function normalizeFilePath(string $filePath): string
    {
        $storageRoot = $this->images->getStorageRoot();
        $trimmedStorageRoot = rtrim($storageRoot, '/\\');

        if ($trimmedStorageRoot === '' || !str_starts_with($filePath, $trimmedStorageRoot)) {
            return $filePath;
        }

        $relativePath = substr($filePath, strlen($trimmedStorageRoot));
        if ($relativePath === '' || str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
            return $filePath;
        }

        return $trimmedStorageRoot . '/' . $relativePath;
    }

    private function getRelativeMediaPath(string $filePath): ?string
    {
        $storageRoot = rtrim($this->images->getStorageRoot(), '/\\') . '/';

        if (!str_starts_with($filePath, $storageRoot)) {
            return null;
        }

        return substr($filePath, strlen($storageRoot));
    }
}
