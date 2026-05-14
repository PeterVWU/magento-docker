<?php
declare(strict_types=1);

namespace Vapewholesaleusa\GcsRemoteStorage\Driver;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Magento\AwsS3\Driver\CachedCredentialsProvider;
use Magento\RemoteStorage\Driver\Adapter\Cache\CacheInterfaceFactory;
use Magento\RemoteStorage\Driver\Adapter\CachedAdapterInterfaceFactory;
use Magento\RemoteStorage\Driver\Adapter\MetadataProviderInterfaceFactory;
use Magento\RemoteStorage\Driver\DriverException;
use Magento\RemoteStorage\Driver\DriverFactoryInterface;
use Magento\RemoteStorage\Driver\RemoteDriverInterface;
use Magento\RemoteStorage\Model\Config;
use Psr\Log\LoggerInterface;

class GcsS3Factory implements DriverFactoryInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly MetadataProviderInterfaceFactory $metadataProviderFactory,
        private readonly CacheInterfaceFactory $cacheInterfaceFactory,
        private readonly CachedAdapterInterfaceFactory $cachedAdapterInterfaceFactory,
        private readonly CachedCredentialsProvider $cachedCredentialsProvider,
        private readonly LoggerInterface $logger,
        private readonly ?string $cachePrefix = null
    ) {
    }

    public function create(): RemoteDriverInterface
    {
        return $this->createConfigured(
            $this->config->getConfig(),
            $this->config->getPrefix()
        );
    }

    public function createConfigured(
        array $config,
        string $prefix,
        string $cacheAdapter = '',
        array $cacheConfig = []
    ): RemoteDriverInterface {
        $config = $this->prepareConfig($config);
        $client = new S3Client($config);
        $adapter = new AwsS3V3Adapter($client, $config['bucket'], $prefix);
        $cache = $this->cacheInterfaceFactory->create(
            $this->cachePrefix ? ['prefix' => $this->cachePrefix] : ['prefix' => md5($config['bucket'] . $prefix)]
        );
        $metadataProvider = $this->metadataProviderFactory->create([
            'adapter' => $adapter,
            'cache' => $cache,
        ]);
        $objectUrl = rtrim($client->getObjectUrl($config['bucket'], './'), '/') . trim($prefix, '\\/') . '/';

        return new GcsS3(
            $adapter,
            $client,
            $this->logger,
            $objectUrl,
            $metadataProvider,
            $config['bucket'],
            $prefix
        );
    }

    private function prepareConfig(array $config): array
    {
        $config['version'] = 'latest';
        $config['path_style'] = $config['path_style'] ?? $config['path-style'] ?? '1';
        $config['use_path_style_endpoint'] = filter_var($config['path_style'], FILTER_VALIDATE_BOOLEAN);

        if (empty($config['credentials']['key']) || empty($config['credentials']['secret'])) {
            $config['credentials'] = $this->cachedCredentialsProvider->get();
        }

        if (empty($config['bucket']) || empty($config['region'])) {
            throw new DriverException(__('Bucket and region are required values'));
        }

        return $config;
    }
}
