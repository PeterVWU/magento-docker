<?php
declare(strict_types=1);

$file = __DIR__ . '/../../vendor/magento/module-open-search/Model/SearchClient.php';
$source = file_get_contents($file);

if ($source === false) {
    fwrite(STDERR, "Unable to read {$file}\n");
    exit(1);
}

if (str_contains($source, "MAGENTO_OPENSEARCH_SSL_VERIFY")) {
    exit(0);
}

$search = <<<'PHP'
        $authString = '';
        if (!empty($options['enableAuth']) && (int)$options['enableAuth'] === 1) {
            $authString = "{$options['username']}:{$options['password']}@";
        }

        $portString = '';
        if (!empty($options['port'])) {
            $portString = ':' . $options['port'];
        }

        $host = $protocol . '://' . $authString . $hostname . $portString;

        $options['hosts'] = [$host];

        return $options;
PHP;

$replace = <<<'PHP'
        if (!empty($options['enableAuth']) && (int)$options['enableAuth'] === 1) {
            $options['BasicAuthentication'] = [$options['username'], $options['password']];
        }

        $portString = '';
        if (!empty($options['port'])) {
            $portString = ':' . $options['port'];
        }

        $host = $protocol . '://' . $hostname . $portString;

        $options['hosts'] = [$host];
        if (getenv('MAGENTO_OPENSEARCH_SSL_VERIFY') === '0') {
            $options['SSLVerification'] = false;
        }

        return $options;
PHP;

$patched = str_replace($search, $replace, $source, $count);
if ($count !== 1) {
    fwrite(STDERR, "OpenSearch client patch target not found in {$file}\n");
    exit(1);
}

if (file_put_contents($file, $patched) === false) {
    fwrite(STDERR, "Unable to write {$file}\n");
    exit(1);
}
