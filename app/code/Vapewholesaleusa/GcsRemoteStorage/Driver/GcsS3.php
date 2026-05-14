<?php
declare(strict_types=1);

namespace Vapewholesaleusa\GcsRemoteStorage\Driver;

use Aws\S3\S3ClientInterface;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException as FlysystemFilesystemException;
use League\Flysystem\UnableToRetrieveMetadata;
use Magento\AwsS3\Driver\AwsS3;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Phrase;
use Magento\RemoteStorage\Driver\Adapter\MetadataProviderInterface;
use Magento\RemoteStorage\Driver\DriverException;
use Psr\Log\LoggerInterface;

class GcsS3 extends AwsS3
{
    private const TEST_FLAG = 'storage.flag';
    private const CONFIG = [];

    private FilesystemAdapter $adapter;
    private S3ClientInterface $client;
    private LoggerInterface $logger;
    private string $objectUrl;
    private string $bucket;
    private string $prefix;
    /** @var array<string, resource> */
    private array $streams = [];

    public function __construct(
        FilesystemAdapter $adapter,
        S3ClientInterface $client,
        LoggerInterface $logger,
        string $objectUrl,
        MetadataProviderInterface $metadataProvider,
        string $bucket,
        string $prefix
    ) {
        parent::__construct($adapter, $logger, $objectUrl, $metadataProvider);
        $this->adapter = $adapter;
        $this->client = $client;
        $this->logger = $logger;
        $this->objectUrl = $objectUrl;
        $this->bucket = $bucket;
        $this->prefix = trim($prefix, '/');
    }

    public function __destruct()
    {
        foreach ($this->streams as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function test(): void
    {
        try {
            $this->putObject(self::TEST_FLAG, '');
        } catch (\Throwable $exception) {
            throw new DriverException(__($exception->getMessage()), $exception);
        }
    }

    public function filePutContents($path, $content, $mode = null): bool|int
    {
        $path = $this->normalizeRelativePath((string)$path, true);
        $config = self::CONFIG;

        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        if (false !== ($imageSize = @getimagesizefromstring((string)$content))) {
            $config['Metadata'] = [
                'image-width' => $imageSize[0],
                'image-height' => $imageSize[1],
            ];
        }

        try {
            $this->putObject($path, (string)$content, $config['Metadata'] ?? []);
            $this->adapter->fileSize($path);
            return strlen((string)$content);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Unable to write GCS object.',
                ['path' => $path, 'exception' => $exception]
            );
            return false;
        }
    }

    public function copy($source, $destination, ?DriverInterface $targetDriver = null): bool
    {
        try {
            $sourcePath = $this->normalizeRelativePath((string)$source, true);
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $this->prefixPath($this->normalizeRelativePath((string)$destination, true)),
                'CopySource' => $this->copySource($sourcePath),
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Unable to copy GCS object.',
                ['source' => $source, 'destination' => $destination, 'exception' => $exception]
            );
            return false;
        }

        return true;
    }

    public function rename($oldPath, $newPath, ?DriverInterface $targetDriver = null): bool
    {
        if ($oldPath === $newPath) {
            return true;
        }

        try {
            $sourcePath = $this->normalizeRelativePath((string)$oldPath, true);
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'Key' => $this->prefixPath($this->normalizeRelativePath((string)$newPath, true)),
                'CopySource' => $this->copySource($sourcePath),
            ]);
            $this->adapter->delete($sourcePath);
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Unable to rename GCS object.',
                ['oldPath' => $oldPath, 'newPath' => $newPath, 'exception' => $exception]
            );
            return false;
        }

        return true;
    }

    public function createDirectory($path, $permissions = 0777): bool
    {
        $path = $this->normalizeRelativePath((string)$path, true);
        if (in_array($path, ['', '.', '/'], true)) {
            return true;
        }

        $segments = array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== '');
        $currentPath = '';

        foreach ($segments as $segment) {
            $currentPath = $currentPath === '' ? $segment : $currentPath . '/' . $segment;
            try {
                $this->putObject($currentPath . '/', '');
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Unable to create GCS directory marker.',
                    ['path' => $currentPath, 'exception' => $exception]
                );
                return false;
            }
        }

        return true;
    }

    public function fileOpen($path, $mode)
    {
        $_mode = str_replace(['b', '+'], '', strtolower((string)$mode));
        if (!in_array($_mode, ['r', 'w', 'a'], true)) {
            throw new FileSystemException(new Phrase('Invalid file open mode "%1".', [$mode]));
        }

        $path = $this->normalizeRelativePath((string)$path, true);

        if (!isset($this->streams[$path])) {
            $stream = tmpfile();
            if ($stream === false) {
                throw new FileSystemException(new Phrase('Unable to create a temporary stream.'));
            }
            $this->streams[$path] = $stream;

            try {
                if ($this->adapter->fileExists($path) && $_mode !== 'w') {
                    fwrite($this->streams[$path], $this->adapter->read($path));
                    if ($_mode !== 'a') {
                        rewind($this->streams[$path]);
                    }
                }
            } catch (FlysystemFilesystemException $exception) {
                $this->logger->error(
                    'Unable to preload GCS stream.',
                    ['path' => $path, 'exception' => $exception]
                );
            }
        }

        return $this->streams[$path];
    }

    public function fileWrite($resource, $data)
    {
        if (!is_resource($resource)) {
            return false;
        }

        $resourcePath = stream_get_meta_data($resource)['uri'];

        foreach ($this->streams as $stream) {
            if (stream_get_meta_data($stream)['uri'] === $resourcePath) {
                return fwrite($stream, (string)$data);
            }
        }

        return false;
    }

    public function fileClose($resource): bool
    {
        if (!is_resource($resource)) {
            return false;
        }

        $meta = stream_get_meta_data($resource);

        foreach ($this->streams as $path => $stream) {
            if (stream_get_meta_data($stream)['uri'] === $meta['uri']) {
                if (isset($meta['seekable']) && $meta['seekable']) {
                    $this->fileSeek($resource, 0);
                }

                $content = stream_get_contents($resource);
                if ($content === false) {
                    $content = '';
                }

                $metadata = [];
                // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
                if (false !== ($imageSize = @getimagesizefromstring($content))) {
                    $metadata = [
                        'image-width' => $imageSize[0],
                        'image-height' => $imageSize[1],
                    ];
                }

                try {
                    $this->putObject($path, $content, $metadata);
                } catch (\Throwable $exception) {
                    $this->logger->error(
                        'Unable to flush stream to GCS object.',
                        ['path' => $path, 'exception' => $exception]
                    );
                    unset($this->streams[$path]);
                    fclose($stream);

                    return false;
                }

                unset($this->streams[$path]);

                return fclose($stream);
            }
        }

        return false;
    }

    public function isFile($path): bool
    {
        $path = $this->normalizeRelativePath((string)$path, true);
        if ($path === '') {
            return false;
        }

        try {
            return $this->adapter->fileExists($path);
        } catch (FlysystemFilesystemException $exception) {
            $this->logger->error(
                'Unable to check GCS file existence.',
                ['path' => $path, 'exception' => $exception]
            );
            return false;
        }
    }

    public function isDirectory($path): bool
    {
        $path = $this->normalizeRelativePath((string)$path, true);
        if (in_array($path, ['', '.', '/'], true)) {
            return true;
        }

        try {
            return $this->adapter->directoryExists($path);
        } catch (FlysystemFilesystemException $exception) {
            $this->logger->error(
                'Unable to check GCS directory existence.',
                ['path' => $path, 'exception' => $exception]
            );
            return false;
        }
    }

    public function getMetadata(string $path): array
    {
        $path = $this->normalizeRelativePath($path, true);

        if ($path === '' || $this->isDirectory($path)) {
            return [
                'path' => $path,
                'type' => self::TYPE_DIR,
                'size' => null,
                'timestamp' => null,
                'visibility' => null,
                'mimetype' => null,
                'dirname' => dirname($path),
                'basename' => basename($path),
            ];
        }

        try {
            $result = $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key' => $this->prefixPath($path),
            ]);
        } catch (\Throwable $exception) {
            throw new UnableToRetrieveMetadata(
                sprintf('Unable to retrieve metadata for GCS object at location: %s.', $path),
                0,
                $exception
            );
        }

        $lastModified = $result['LastModified'] ?? null;
        $timestamp = $lastModified instanceof \DateTimeInterface ? $lastModified->getTimestamp() : null;
        $metadata = $result['Metadata'] ?? [];
        $data = [
            'path' => $path,
            'type' => self::TYPE_FILE,
            'size' => isset($result['ContentLength']) ? (int)$result['ContentLength'] : null,
            'timestamp' => $timestamp,
            'visibility' => null,
            'mimetype' => $result['ContentType'] ?? null,
            'dirname' => dirname($path),
            'basename' => basename($path, '.' . pathinfo($path, PATHINFO_EXTENSION)),
        ];

        if (isset($metadata['image-width'], $metadata['image-height'])) {
            $data['extra'] = $metadata;
        }

        return $data;
    }

    public function stat($path): array
    {
        $result = [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'atime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0,
            'size' => 0,
            'type' => '',
            'mtime' => 0,
            'disposition' => null,
        ];

        try {
            $metadata = $this->getMetadata((string)$path);
        } catch (UnableToRetrieveMetadata) {
            return $result;
        }

        $result['type'] = $metadata['type'] ?? '';
        $result['size'] = (int)($metadata['size'] ?? 0);
        $result['mtime'] = (int)($metadata['timestamp'] ?? 0);

        return $result;
    }

    private function normalizeRelativePath(string $path, bool $fixPath = false): string
    {
        $relativePath = str_replace($this->normalizeAbsolutePath(''), '', $path);

        if ($fixPath) {
            $relativePath = $this->fixPath($relativePath);
        }

        return $relativePath;
    }

    private function normalizeAbsolutePath(string $path): string
    {
        $path = str_replace($this->objectUrl, '', $path);

        return $this->objectUrl . ltrim($path, '/');
    }

    private function fixPath(string $path): string
    {
        return ltrim($path, '/');
    }

    /**
     * @param array<string, string|int> $metadata
     */
    private function putObject(string $path, string $content, array $metadata = []): void
    {
        $arguments = [
            'Bucket' => $this->bucket,
            'Key' => $this->prefixPath($path),
            'Body' => $content,
        ];

        if ($metadata) {
            $arguments['Metadata'] = $metadata;
        }

        $this->client->putObject($arguments);
    }

    private function prefixPath(string $path): string
    {
        $path = $this->fixPath($path);

        if ($this->prefix === '') {
            return $path;
        }

        return $this->prefix . '/' . $path;
    }

    private function copySource(string $sourcePath): string
    {
        return $this->bucket . '/' . str_replace('%2F', '/', rawurlencode($this->prefixPath($sourcePath)));
    }
}
