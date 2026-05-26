<?php

declare(strict_types=1);

namespace Vapewholesaleusa\MageWorxOrderEditor\ViewModel;

/**
 * Class Comments
 * @package Vapewholesaleusa\MageWorxOrderEditor\ViewModel
 */
class Comments implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    public function __construct(\Magento\Framework\AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Check allow to add comment
     *
     * @return bool
     */
    public function canEditComment()
    {
        return $this->authorization->isAllowed('MageWorx_OrderEditor::edit_comments');
    }

    /**
     * Replace Comment
     *
     * @param $comment
     * @return string
     */
    public function replaceComment($comment)
    {
        $comment = preg_replace('/\s*id="[^"]*"/i', '', (string)$comment);
        return  preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$comment);
    }
}
