<?php

namespace Vapewholesaleusa\OrderSource\Block\Adminhtml\Block\System\Config\Button;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Button extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Vapewholesaleusa_OrderSource::system/config/button/button.phtml';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Button constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    )
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * @param AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setElement($element);
        $url = $this->getUrl('order_source/Order/CopyQty');
        $data = $this->getData();
        $html = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')
            ->setData($data)
            ->setData([
                'id' => 'vapewholesaleusa_ordersource_system_config_button_button',
                'label' => __('Update Stock'),
                'onclick' => 'javascript:check(); return false;'
            ])->toHtml();
        $html .= '<script>
                    require(["jquery"], function ($) {
                        $("#vapewholesaleusa_ordersource_system_config_button_button").click(function() {
                            var baseSource = $("#ordersource_source_copier_source_source_code").val();
                            var targetSource = $("#ordersource_source_copier_target_source_code").val();
                            $.ajax({
                                    url: "' . $url . '",
                                    type: "POST",
                                    data: {
                                        "form_key": window.FORM_KEY,
                                        "data": {
                                            "source_code": baseSource,
                                            "target_code": targetSource
                                        }
                                    },
                                    showLoader: true,
                                     complete: function (data) {
                                        alert(data.responseJSON.message)
                                    }
                                });
                        });
                    });
                </script>';
        return $html;
    }
}
{

}
