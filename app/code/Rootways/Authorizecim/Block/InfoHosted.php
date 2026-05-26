<?php
/**
 * Authorizecim Payment Module.
 *
 * @category  Payment Integration
 * @package   Rootways_Authorizecim
 * @author    Developer RootwaysInc <developer@rootways.com>
 * @copyright 2023 Rootways Inc. (https://www.rootways.com)
 * @license   Rootways Custom License
 * @link      https://www.rootways.com/pub/media/extension_doc/license_agreement.pdf
 */
namespace Rootways\Authorizecim\Block;

/**
 * Transaction information in the admin order for the Hosted Payment method.
 *
 * InfoHosted
 */
class InfoHosted extends \Magento\Payment\Block\Info
{
    protected $_isCheckoutProgressBlockFlag = true;

    /**
     * @var \Rootways\Authorizecim\Helper\Data
     */
    protected $customHelper;

    /**
     * @var \Magento\Payment\Model\Config
     */
    protected $_paymentConfig;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction
     */
    protected $paymentModel;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    protected $_avsCode = [
        'A' => 'The street address matched, but the postal code did not',
        'B' => 'No address information was provided',
        'E'=> 'The AVS check returned an error',
        'G'=> 'The card was issued by a bank outside the U.S. and does not support AVS',
        'N'=> 'Neither the street address nor postal code matched',
        'P'=> 'AVS is not applicable for this transaction',
        'R'=> ' s unavailable or timed out',
        'S'=> 'AVS is not supported by card issuer',
        'U'=> 'Address information is unavailable',
        'W'=> 'The US ZIP+4 code matches, but the street address does not',
        'X'=> 'Both the street address and the US ZIP+4 code matched',
        'Y'=> 'The street address and postal code matched',
        'Z'=> 'The postal code matched, but the street address did not.'
    ];

    protected $_cvvCode = [
        'M' => 'CVV matched',
        'N' => 'CVV did not match',
        'P' => 'CVV was not processed',
        'S' => 'CVV should have been present but was not indicated',
        'U' => 'The issuer was unable to process the CVV check'
    ];

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Payment\Model\Config $paymentConfig
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Sales\Model\Order\Payment\Transaction $payment
     * @param \Rootways\Authorizecim\Helper\Data $customHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Sales\Model\Order\Payment\Transaction $payment,
        \Rootways\Authorizecim\Helper\Data $customHelper,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->_paymentConfig = $paymentConfig;
        $this->paymentModel = $payment;
        $this->customHelper = $customHelper;
        parent::__construct($context, $data);
    }

    public function setCheckoutProgressBlock($flag)
    {
        $this->_isCheckoutProgressBlockFlag = $flag;
        return $this;
    }

    public function getCcTypeName()
    {
        $types = $this->_paymentConfig->getCcTypes();
        $ccType = $this->getInfo()->getCcType();
        if (isset($types[$ccType])) {
            return $types[$ccType];
        }
        return empty($ccType) ? __('N/A') : $ccType;
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        $info = $this->getInfo();
        if ($ccType = $this->getCcTypeName()) {
            $data[(string) __('Credit Card Type')] = $ccType;
        }
        if ($info->getCcLast4()) {
            $data[(string) __('Credit Card Number')] = sprintf('xxxx-%s', $info->getCcLast4());
        }

        if ($this->customHelper->checkAdmin() == 'adminhtml') {
            if (isset($info->getAdditionalInformation()['order_tra_id'])) {
                $data[(string) __('Transaction ID')] = $info->getAdditionalInformation()['order_tra_id'];
            }

            if (isset($info->getAdditionalInformation()['auth_code'])) {
                $data[(string) __('Authorization Code')] = $info->getAdditionalInformation()['auth_code'];
            }

            if ($info->getCcAvsStatus()) {
                if (isset($this->_avsCode[trim($info->getCcAvsStatus())])) {
                    $data[(string) __('AVS Response')] = $info->getCcAvsStatus().' ('.$this->_avsCode[trim($info->getCcAvsStatus())].')';
                } else {
                    $data[(string) __('AVS Response')] = $info->getCcAvsStatus();
                }
            }
            if ($info->getCcCidStatus()) {
                if (isset($this->_cvvCode[trim($info->getCcCidStatus())])) {
                    $data[(string) __('CVV Response')] = $info->getCcCidStatus().' ('.$this->_cvvCode[trim($info->getCcCidStatus())].')';
                } else {
                    $data[(string) __('CVV Response')] = $info->getCcCidStatus();
                }
            }
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

    public function getSpecificInformation()
    {
        return $this->_prepareSpecificInformation()->getData();
    }
}
