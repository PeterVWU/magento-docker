<?php
namespace Rootways\Authorizecim\Model\Plugin\ResponseHeader;

/**
 * Skip X-FRAME-OPTIONS header for Authorize.net module.
 * XFrameOptions
 */
class XFrameOptions extends \Magento\Framework\App\Response\HeaderProvider\XFrameOptions
{
    /**
     * @param \Magento\Framework\App\Request\Http $request
     * @param string $xFrameOpt
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        $xFrameOpt = 'SAMEORIGIN'
    ) {
        // Skip this for the iFrame page
        if ($request->getModuleName() == 'rootways_authorizecim') {
            return;
        }

        return parent::__construct($xFrameOpt);
    }
}
