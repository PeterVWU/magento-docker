<?php
declare(strict_types=1);

namespace Vapewholesaleusa\MageWorxOrderEditor\Plugin\Block\Adminhtml\Order\View\Tab;

use Magento\Sales\Block\Adminhtml\Order\View\Tab\History;
use Magento\Sales\Helper\Admin;

/**
 * Class HistoryPlugin
 * @package Vapewholesaleusa\MageWorxOrderEditor\Plugin\Block\Adminhtml\Order\View\Tab
 */
class HistoryPlugin
{
    /**
     * @param Admin $adminHelper
     */
    public function __construct(
        private readonly Admin $adminHelper
    ) {
    }

    /**
     * @param History $subject
     * @param callable $proceed
     * @param array $item
     * @return string
     */
    public function aroundGetItemComment(
        History $subject,
        callable $proceed,
        array $item
    ) {
        $allowedTags = [
            "a", "b", "strong", "h1", "h2", "h3", "h4", "h5", "br", "em", "hr",
            "i", "li", "ol", "p", "s", "span", "table", "tbody", "tr", "td", "u", "ul"
        ];

        $rawComment = $item['comment'] ?? '';
        $comment = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/u', '', (string)$rawComment);
        $comment = preg_replace('/\s*id="[^"]*"/i', '', (string)$comment);

        return $comment !== ''
            ? $this->adminHelper->escapeHtmlWithLinks($comment, $allowedTags)
            : '';
    }
}
