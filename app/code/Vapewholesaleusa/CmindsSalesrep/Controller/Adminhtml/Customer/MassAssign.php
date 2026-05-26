<?php

declare(strict_types=1);

namespace Vapewholesaleusa\CmindsSalesrep\Controller\Adminhtml\Customer;

class MassAssign extends \Magento\Backend\App\Action
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context                                               $context,
        private readonly \Magento\Ui\Component\MassAction\Filter                          $filter,
        private readonly \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory,
        private readonly \Magento\Customer\Api\CustomerRepositoryInterface                $customerRepository,
    ) {
        parent::__construct($context);
    }

    /**
     * Executes mass action for assigning a sales representative to orders.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute(): \Magento\Backend\Model\View\Result\Redirect
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $this->massAction($collection);
            $this->messageManager->addSuccessMessage(
                __('Sales representative has been successfully assigned to the customers(s)')
            );
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('customer/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('customer/index');
        }
    }

    /**
     * Assigns the sales representative ID to customers in the collection.
     *
     * @param $collection
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     */
    protected function massAction($collection): void
    {
        $postData = $this->getRequest()->getParams();

        if (isset($postData['salesrep_id'])) {
            foreach ($collection as $item) {
                $customerModel = $this->customerRepository->getById($item->getId());
                $customerModel->setCustomAttribute('salesrep_rep_id', (int)$postData['salesrep_id']);
                $this->customerRepository->save($customerModel);
            }
        }
    }
}
