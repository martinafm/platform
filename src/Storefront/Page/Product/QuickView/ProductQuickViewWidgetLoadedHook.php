<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Product\QuickView;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Script\Execution\Awareness\SalesChannelContextAwareTrait;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\PageLoadedHook;

/**
 * Triggered when the ProductQuickViewWidget is loaded
 *
 * @hook-use-case data_loading
 *
 * @since 6.4.8.0
 */
#[Package('storefront')]
class ProductQuickViewWidgetLoadedHook extends PageLoadedHook
{
    use SalesChannelContextAwareTrait;

    final public const HOOK_NAME = 'product-quick-view-widget-loaded';

    public function __construct(
        private readonly MinimalQuickViewPage $page,
        SalesChannelContext $context
    ) {
        parent::__construct($context->getContext());
        $this->salesChannelContext = $context;
    }

    public function getName(): string
    {
        return self::HOOK_NAME;
    }

    public function getPage(): MinimalQuickViewPage
    {
        return $this->page;
    }
}
