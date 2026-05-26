<?php
declare(strict_types=1);

namespace Vapewholesaleusa\CustomerLicense\Controller\Adminhtml\Notification;

use Magento\Framework\App\ResponseInterface;
use Vapewholesaleusa\CustomerLicense\Cron\CheckLicenseExpiration;

class Run extends \Magento\Backend\App\Action
{
    /**
     * Construct
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param CheckLicenseExpiration $cron
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly CheckLicenseExpiration $cron
    ){
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {

            $this->cron->execute();

            $this->messageManager->addSuccessMessage(
                __('License notification test executed.')
            );

        } catch (\Exception $e) {

            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect(
            'adminhtml/system_config/edit/section/customer_license'
        );
    }

    /**
     * Determines whether current user is allowed to access Action
     *
     * @return bool
     */
    protected function _isAllowed() {
        return $this->_authorization->isAllowed('Vapewholesaleusa_CustomerLicense::customer_license');

    }
}
