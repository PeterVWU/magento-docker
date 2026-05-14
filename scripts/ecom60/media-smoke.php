<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\RemoteStorage\Model\Config as RemoteStorageConfig;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var RemoteStorageConfig $remoteStorageConfig */
$remoteStorageConfig = $objectManager->get(RemoteStorageConfig::class);
/** @var Filesystem $filesystem */
$filesystem = $objectManager->get(Filesystem::class);

$mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
$rootPath = sprintf(
    'ecom60-smoke/%s-%s',
    gmdate('YmdHis'),
    bin2hex(random_bytes(4))
);
$relativePath = $rootPath . '/write-read-delete.txt';
$payload = sprintf(
    "ECOM-60 remote media smoke\nUTC: %s\nDriver: %s\nPrefix: %s\n",
    gmdate('c'),
    $remoteStorageConfig->getDriver(),
    $remoteStorageConfig->getPrefix()
);

$pathsToDelete = [$rootPath];

try {
    $mediaDirectory->create($rootPath . '/nested');
    if (!$mediaDirectory->isDirectory($rootPath . '/nested')) {
        throw new RuntimeException("Smoke directory was not visible after create: {$rootPath}/nested");
    }

    $mediaDirectory->writeFile($relativePath, $payload);

    if (!$mediaDirectory->isExist($relativePath)) {
        throw new RuntimeException("Smoke file was not visible after write: {$relativePath}");
    }

    $readPayload = $mediaDirectory->readFile($relativePath);
    if ($readPayload !== $payload) {
        throw new RuntimeException("Smoke file payload mismatch: {$relativePath}");
    }

    $stat = $mediaDirectory->stat($relativePath);
    if (($stat['type'] ?? '') !== 'file' || (int)($stat['size'] ?? 0) !== strlen($payload)) {
        throw new RuntimeException("Smoke file stat mismatch: {$relativePath}");
    }

    $copyPath = $rootPath . '/nested/copied file.txt';
    $mediaDirectory->copyFile($relativePath, $copyPath);
    if ($mediaDirectory->readFile($copyPath) !== $payload) {
        throw new RuntimeException("Smoke copied file payload mismatch: {$copyPath}");
    }

    $renamedPath = $rootPath . '/nested/renamed-file.txt';
    $mediaDirectory->renameFile($copyPath, $renamedPath);
    if ($mediaDirectory->isExist($copyPath) || $mediaDirectory->readFile($renamedPath) !== $payload) {
        throw new RuntimeException("Smoke renamed file state mismatch: {$renamedPath}");
    }

    $streamPath = $rootPath . '/stream-write.txt';
    $streamPayload = "stream-line-1\nstream-line-2\n";
    $stream = $mediaDirectory->openFile($streamPath, 'w');
    $stream->write('stream-line-1' . PHP_EOL);
    $stream->write('stream-line-2' . PHP_EOL);
    $stream->close();
    if ($mediaDirectory->readFile($streamPath) !== $streamPayload) {
        throw new RuntimeException("Smoke stream file payload mismatch: {$streamPath}");
    }

    $imagePath = $rootPath . '/image.png';
    $imagePayload = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=',
        true
    );
    if ($imagePayload === false) {
        throw new RuntimeException('Smoke image payload could not be decoded.');
    }
    $mediaDirectory->writeFile($imagePath, $imagePayload);
    $imageStat = $mediaDirectory->stat($imagePath);
    if (($imageStat['type'] ?? '') !== 'file' || (int)($imageStat['size'] ?? 0) !== strlen($imagePayload)) {
        throw new RuntimeException("Smoke image stat mismatch: {$imagePath}");
    }

    $mediaDirectory->delete($relativePath);
    if ($mediaDirectory->isExist($relativePath)) {
        throw new RuntimeException("Smoke file was still visible after delete: {$relativePath}");
    }
} finally {
    foreach ($pathsToDelete as $pathToDelete) {
        $mediaDirectory->delete($pathToDelete);
    }
}

printf("driver=%s\n", $remoteStorageConfig->getDriver());
printf("prefix=%s\n", $remoteStorageConfig->getPrefix());
printf("path=%s\n", $rootPath);
printf("result=write-read-copy-rename-stream-stat-delete-ok\n");
