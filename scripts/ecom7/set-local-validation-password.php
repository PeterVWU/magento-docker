<?php

use Magento\Framework\App\Bootstrap;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Encryption\EncryptorInterface;

require __DIR__ . '/../../app/bootstrap.php';

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if ($email === null || $password === null) {
    fwrite(STDERR, "Usage: php scripts/ecom7/set-local-validation-password.php <email> <password>\n");
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
$customerRegistry = $objectManager->get(CustomerRegistry::class);
$encryptor = $objectManager->get(EncryptorInterface::class);

$customer = $customerRepository->get($email);
$secureData = $customerRegistry->retrieveSecureData((int) $customer->getId());
$secureData->setPasswordHash($encryptor->getHash($password, true));
$customerRepository->save($customer);

printf("Set local validation password for customer %d.\n", (int) $customer->getId());
