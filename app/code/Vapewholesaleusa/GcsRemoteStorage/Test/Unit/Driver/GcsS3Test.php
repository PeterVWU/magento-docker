<?php
declare(strict_types=1);

namespace Vapewholesaleusa\GcsRemoteStorage\Test\Unit\Driver;

use Aws\Api\Service;
use Aws\CommandInterface;
use Aws\Credentials\CredentialsInterface;
use Aws\Result;
use Aws\ResultInterface;
use Aws\ResultPaginator;
use Aws\S3\S3ClientInterface;
use Aws\Waiter;
use GuzzleHttp\Promise\PromiseInterface;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use Magento\RemoteStorage\Driver\Adapter\MetadataProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\NullLogger;
use Vapewholesaleusa\GcsRemoteStorage\Driver\GcsS3;

class GcsS3Test extends TestCase
{
    private RecordingS3Client $client;
    private FilesystemAdapter $adapter;
    private GcsS3 $driver;

    protected function setUp(): void
    {
        $this->client = new RecordingS3Client();
        $this->adapter = $this->createMock(FilesystemAdapter::class);
        $metadataProvider = $this->createMock(MetadataProviderInterface::class);
        $this->driver = new GcsS3(
            $this->adapter,
            $this->client,
            new NullLogger(),
            'https://storage.googleapis.com/vwu-magento-media/ecom60/',
            $metadataProvider,
            'vwu-magento-media',
            'ecom60'
        );
    }

    public function testFilePutContentsWritesWithoutObjectAcl(): void
    {
        $this->adapter->method('fileSize')->with('media/test.txt')->willReturn(new FileAttributes('media/test.txt', 7));

        $result = $this->driver->filePutContents('media/test.txt', 'payload');

        self::assertSame(7, $result);
        self::assertCount(1, $this->client->calls['putObject']);
        $arguments = $this->client->calls['putObject'][0];
        self::assertSame('vwu-magento-media', $arguments['Bucket']);
        self::assertSame('ecom60/media/test.txt', $arguments['Key']);
        self::assertSame('payload', $arguments['Body']);
        self::assertArrayNotHasKey('ACL', $arguments);
        self::assertArrayNotHasKey('visibility', $arguments);
    }

    public function testCopyAndRenameUseEncodedCopySourceWithoutObjectAcl(): void
    {
        $this->adapter->expects(self::once())->method('delete')->with('media/source file.txt');

        self::assertTrue($this->driver->copy('media/source file.txt', 'media/copied file.txt'));
        self::assertTrue($this->driver->rename('media/source file.txt', 'media/renamed file.txt'));

        self::assertSame(
            'vwu-magento-media/ecom60/media/source%20file.txt',
            $this->client->calls['copyObject'][0]['CopySource']
        );
        self::assertSame(
            'vwu-magento-media/ecom60/media/source%20file.txt',
            $this->client->calls['copyObject'][1]['CopySource']
        );
        self::assertArrayNotHasKey('ACL', $this->client->calls['copyObject'][0]);
        self::assertSame('ecom60/media/copied file.txt', $this->client->calls['copyObject'][0]['Key']);
        self::assertSame('ecom60/media/renamed file.txt', $this->client->calls['copyObject'][1]['Key']);
    }

    public function testFileCloseFlushesStreamToGcsWithoutAcl(): void
    {
        $this->adapter->method('fileExists')->with('media/stream.txt')->willReturn(false);

        $stream = $this->driver->fileOpen('media/stream.txt', 'w');
        $this->driver->fileWrite($stream, 'stream payload');

        self::assertTrue($this->driver->fileClose($stream));
        self::assertSame('ecom60/media/stream.txt', $this->client->calls['putObject'][0]['Key']);
        self::assertSame('stream payload', $this->client->calls['putObject'][0]['Body']);
        self::assertArrayNotHasKey('ACL', $this->client->calls['putObject'][0]);
    }

    public function testGetMetadataAndStatUseHeadObjectResult(): void
    {
        $this->adapter->method('directoryExists')->willReturn(false);
        $this->client->responses['headObject'] = [
            'ContentLength' => 123,
            'ContentType' => 'image/png',
            'LastModified' => new \DateTimeImmutable('@1710000000'),
            'Metadata' => [
                'image-width' => '640',
                'image-height' => '480',
            ],
        ];

        $metadata = $this->driver->getMetadata('media/image.png');
        $stat = $this->driver->stat('media/image.png');

        self::assertSame('media/image.png', $metadata['path']);
        self::assertSame('file', $metadata['type']);
        self::assertSame(123, $metadata['size']);
        self::assertSame(1710000000, $metadata['timestamp']);
        self::assertSame('image/png', $metadata['mimetype']);
        self::assertSame('640', $metadata['extra']['image-width']);
        self::assertSame('ecom60/media/image.png', $this->client->calls['headObject'][0]['Key']);
        self::assertSame('file', $stat['type']);
        self::assertSame(123, $stat['size']);
        self::assertSame(1710000000, $stat['mtime']);
    }

    public function testCreateDirectoryCreatesMarkersForEachPathSegment(): void
    {
        self::assertTrue($this->driver->createDirectory('media/nested/path'));

        self::assertSame('ecom60/media/', $this->client->calls['putObject'][0]['Key']);
        self::assertSame('ecom60/media/nested/', $this->client->calls['putObject'][1]['Key']);
        self::assertSame('ecom60/media/nested/path/', $this->client->calls['putObject'][2]['Key']);
        self::assertArrayNotHasKey('ACL', $this->client->calls['putObject'][0]);
    }
}

class RecordingS3Client implements S3ClientInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $calls = [];
    /** @var array<string, array<string, mixed>> */
    public array $responses = [];

    public function __call($name, array $arguments): ResultInterface
    {
        $this->calls[$name][] = $arguments[0] ?? [];

        return new Result($this->responses[$name] ?? []);
    }

    public function createPresignedRequest(CommandInterface $command, $expires, array $options = []): RequestInterface
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getObjectUrl($bucket, $key): string
    {
        return sprintf('https://storage.googleapis.com/%s/%s', $bucket, ltrim((string)$key, '/'));
    }

    public function getCommand($name, array $args = []): CommandInterface
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function execute(CommandInterface $command): ResultInterface
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function executeAsync(CommandInterface $command): PromiseInterface
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getCredentials(): CredentialsInterface|PromiseInterface
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getRegion(): string
    {
        return 'auto';
    }

    public function getEndpoint(): UriInterface
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getApi(): Service
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getConfig($option = null): mixed
    {
        return null;
    }

    public function getHandlerList(): \Aws\HandlerList
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getIterator($name, array $args = []): \Iterator
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getPaginator($name, array $args = []): ResultPaginator
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function waitUntil($name, array $args = []): void
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function getWaiter($name, array $args = []): Waiter
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function doesBucketExist($bucket)
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function doesBucketExistV2($bucket, $accept403)
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function doesObjectExist($bucket, $key, array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function doesObjectExistV2($bucket, $key, $includeDeleteMarkers = false, array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function registerStreamWrapper()
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function registerStreamWrapperV2()
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function deleteMatchingObjects($bucket, $prefix = '', $regex = '', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function deleteMatchingObjectsAsync($bucket, $prefix = '', $regex = '', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function upload($bucket, $key, $body, $acl = 'private', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function uploadAsync($bucket, $key, $body, $acl = 'private', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function copy($fromBucket, $fromKey, $destBucket, $destKey, $acl = 'private', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function copyAsync($fromBucket, $fromKey, $destBucket, $destKey, $acl = 'private', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function uploadDirectory($directory, $bucket, $keyPrefix = null, array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function uploadDirectoryAsync($directory, $bucket, $keyPrefix = null, array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function downloadBucket($directory, $bucket, $keyPrefix = '', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function downloadBucketAsync($directory, $bucket, $keyPrefix = '', array $options = [])
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function determineBucketRegion($bucketName)
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }

    public function determineBucketRegionAsync($bucketName)
    {
        throw new \BadMethodCallException('Not implemented for this test fake.');
    }
}
