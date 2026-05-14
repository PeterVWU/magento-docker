<?php
declare(strict_types=1);

use Aws\S3\S3Client;

require __DIR__ . '/../../vendor/autoload.php';

$credentialFile = $argv[1] ?? '';
if ($credentialFile === '' || !is_readable($credentialFile)) {
    fwrite(STDERR, "Usage: php gcs-s3-compat-smoke.php /path/to/hmac-credential.txt\n");
    exit(66);
}

$credential = file_get_contents($credentialFile);
if (
    !preg_match('/accessId:\s*(\S+)/', (string)$credential, $access)
    || !preg_match('/^secret:\s*(\S+)/m', (string)$credential, $secret)
) {
    fwrite(STDERR, "Credential file must contain accessId and secret fields.\n");
    exit(66);
}

$client = new S3Client([
    'version' => 'latest',
    'region' => getenv('ECOM60_GCS_REGION') ?: 'auto',
    'endpoint' => getenv('ECOM60_GCS_ENDPOINT') ?: 'https://storage.googleapis.com',
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key' => $access[1],
        'secret' => $secret[1],
    ],
    'http' => [
        'connect_timeout' => 5,
        'timeout' => 15,
    ],
]);

$bucket = getenv('ECOM60_GCS_BUCKET') ?: 'vwu-magento-media';
$prefix = trim(getenv('ECOM60_GCS_PREFIX') ?: 'ecom60', '/');
$baseKey = $prefix . '/aws-sdk-compat-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(4));

$noAclKey = $baseKey . '-no-acl.txt';
$aclKey = $baseKey . '-private-acl.txt';

$client->putObject([
    'Bucket' => $bucket,
    'Key' => $noAclKey,
    'Body' => "ecom60 no-acl smoke\n",
]);
$body = (string)$client->getObject([
    'Bucket' => $bucket,
    'Key' => $noAclKey,
])['Body'];
$client->deleteObject([
    'Bucket' => $bucket,
    'Key' => $noAclKey,
]);

printf("bucket=%s\n", $bucket);
printf("no_acl_result=ok\n");
printf("no_acl_key=%s\n", $noAclKey);
printf("no_acl_body_bytes=%d\n", strlen($body));

try {
    $client->putObject([
        'Bucket' => $bucket,
        'Key' => $aclKey,
        'Body' => "ecom60 private-acl smoke\n",
        'ACL' => 'private',
    ]);
    $client->deleteObject([
        'Bucket' => $bucket,
        'Key' => $aclKey,
    ]);
    printf("private_acl_result=ok\n");
    printf("private_acl_key=%s\n", $aclKey);
} catch (Throwable $exception) {
    printf("private_acl_result=fail\n");
    printf("private_acl_error=%s: %s\n", get_class($exception), $exception->getMessage());
}
