<?php
namespace Rootways\Authorizecim\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Config\Model\ResourceModel\Config as ResourceModelConfig;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filter\StripTags;
use Magento\Framework\HTTP\Client\Curl;
use Rootways\Authorizecim\Helper\Data;

class ValidateKey extends Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Rootways_Authorizecim::config_authorizecim';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var StripTags
     */
    private $tagFilter;

    /**
     * @var Data
     */
    protected $rwHelper;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var ResourceModelConfig
     */
    protected $resourceConfig;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param StripTags $tagFilter
     * @param Data $rwHelper
     * @param Curl $curl
     * @param ResourceModelConfig $resourceConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        StripTags $tagFilter,
        Data $rwHelper,
        Curl $curl,
        ResourceModelConfig $resourceConfig
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tagFilter = $tagFilter;
        $this->rwHelper = $rwHelper;
        $this->curl = $curl;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * Check for connection to server
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $output = [
            'success' => false,
            'errorMessage' => '',
        ];
        $options = $this->getRequest()->getParams();

        try {
            $isMultiStore =  $this->rwHelper->getConfig('rootways_authorizecim/general/ismultistore');
            $u = $isMultiStore == 1 ? $this->rwHelper->getStoreBackEndBaseUrl() : $this->rwHelper->getStoreBaseUrl();
            $l = $options['license'];
            $url = base64_decode($this->rwHelper->surl()) ."?u=".$u. "&l=".$l. "&extname=m2_authorizecim";

            if (empty($l)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('License Key is missing.')
                );
            }
            $this->curl->get($url);
            $result = $this->curl->getBody();

            if (!$this->rwHelper->isJSON($result)) {
                $output['errorMessage'] = __('We are sorry to inform you that activating your license key seems to have an issue. We suggest you try again later.');
            }
            $act_data = $this->rwHelper->getJsonDecode($result);
            if (isset($act_data['status']) && $act_data['status'] == '0') {
                $output['errorMessage'] = __('Rootways license key validation process did not pass due to incorrect information provided.');
            } else {
                $this->resourceConfig->saveConfig('rootways_authorizecim/general/lcstatus', date("Y-m-d"), 'default', 0);
                $output['success'] = true;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $output['errorMessage'] = $e->getMessage();
        } catch (\Exception $e) {
            $message = __($e->getMessage());
            $output['errorMessage'] = $this->tagFilter->filter($message);
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($output);
    }
}
