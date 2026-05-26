<?php

namespace Vapewholesaleusa\OrderSource\Services;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Integration\Model\IntegrationFactory;
use Magento\User\Model\UserFactory;

class GetClientSourceService
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var IntegrationFactory
     */
    private $integrationFactory;

    /**
     * @param UserContextInterface $userContext
     * @param UserFactory $userFactory
     * @param IntegrationFactory $integrationFactory
     */
    public function __construct(
        UserContextInterface $userContext,
        UserFactory          $userFactory,
        IntegrationFactory   $integrationFactory
    )
    {
        $this->userContext = $userContext;
        $this->userFactory = $userFactory;
        $this->integrationFactory = $integrationFactory;
    }

    /**
     * @return string
     */
    public function execute()
    {
        $userType = $this->userContext->getUserType();
        if ($userType == UserContextInterface::USER_TYPE_INTEGRATION) {
            $integration = $this->integrationFactory->create()->load($this->userContext->getUserId());
            if ($integration->getSourceCode()) {
                return $integration->getSourceCode();
            }
        }

        if ($userType == UserContextInterface::USER_TYPE_ADMIN) {
            $user = $this->userFactory->create()->load($this->userContext->getUserId());
            if ($user->getSourceCode()) {
                return $user->getSourceCode();
            }
        }

        return null;
    }
}
