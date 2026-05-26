<?php
namespace Rootways\Authorizecim\Controller\HostedForm;

use Magento\Framework\App\Action\Context;

class Iframecommunicator extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $rData = '<script type="text/javascript">
		//<![CDATA[
			function callParentFunction(str) {
				if (str && str.length > 0
					&& window.parent
					&& window.parent.parent
					&& window.parent.parent.AuthorizeNetIFrame
					&& window.parent.parent.AuthorizeNetIFrame.onReceiveCommunication)
					{
// Errors indicate a mismatch in domain between the page containing the iframe and this page.
						window.parent.parent.AuthorizeNetIFrame.onReceiveCommunication(str);
					}
				}

			function receiveMessage(event) {
				if (event && event.data) {
					callParentFunction(event.data);
					}
				}

				if (window.addEventListener) {
					window.addEventListener("message", receiveMessage, false);
					} else if (window.attachEvent) {
						window.attachEvent("onmessage", receiveMessage);
					}

				if (window.location.hash && window.location.hash.length > 1) {
					callParentFunction(window.location.hash.substring(1));
					}
		//]]/>
	</script>';

        $result = $this->resultRawFactory->create();
        $result->setContents($rData);
        return $result;
    }
}
