<?php

namespace Oro\Bundle\ShoppingListBundle\Twig;

use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to check if only one shopping list is enabled for the current storefront user:
 *   - is_one_shopping_list_enabled
 *
 * Provides a Twig function to get shopping list storefront urls:
 *   - shopping_list_frontend_url
 *
 * Provides Twig functions to get required data for shopping list widget view:
 *   - get_shopping_list_widget_buttons
 *   - get_visible_product
 *   - is_line_item_product_viewable
 */
class ShoppingListExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var \WeakMap<Product, bool>|null */
    private ?\WeakMap $variantFlags = null;

    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('is_one_shopping_list_enabled', [$this, 'isOnlyOneShoppingListEnabled']),
            new TwigFunction('oro_shopping_list_frontend_url', [$this, 'getShoppingListFrontendUrl']),
            new TwigFunction('get_shopping_list_widget_buttons', [$this, 'getShoppingListWidgetButtons']),
            new TwigFunction('get_visible_product', [$this, 'getVisibleProduct']),
            new TwigFunction('is_line_item_product_viewable', [$this, 'isLineItemProductViewable']),
        ];
    }

    public function isOnlyOneShoppingListEnabled(): bool
    {
        return $this->getShoppingListLimitManager()->isOnlyOneEnabled();
    }

    public function getShoppingListFrontendUrl(?ShoppingList $shoppingList = null): string
    {
        return $this->getShoppingListUrlProvider()->getFrontendUrl($shoppingList);
    }

    public function getShoppingListWidgetButtons(ShoppingList $shoppingList): array
    {
        $buttons = $this->getLayoutButtonProvider()->getAll($shoppingList);
        foreach ($buttons as $key => $button) {
            if (!\in_array($button->getName(), $this->getAvailableButtonNames(), true)) {
                unset($buttons[$key]);
            }
        }

        return $buttons;
    }

    private function getAvailableButtonNames(): array
    {
        return [
            'b2b_flow_checkout_start_from_shoppinglist',
        ];
    }

    public function getVisibleProduct(LineItem $lineItem): Product
    {
        $featureChecker = $this->getFeatureChecker();
        $product = $lineItem->getProduct();
        if (
            $featureChecker->isFeatureEnabled('simple_variations_view_restriction')
            && $this->isVariant($product)
        ) {
            return $lineItem->getParentProduct() ?? $product;
        }

        return $product;
    }

    /**
     * Checks whether the view page of the product returned by {@see getVisibleProduct} is available
     * for the given line item, i.e. the product is not a variant restricted from view.
     */
    public function isLineItemProductViewable(LineItem $lineItem): bool
    {
        if (null !== $lineItem->getParentProduct()) {
            return true;
        }

        return
            !$this->getFeatureChecker()->isFeatureEnabled('simple_variations_view_restriction')
            || !$this->isVariant($lineItem->getProduct());
    }

    private function isVariant(Product $product): bool
    {
        $this->variantFlags ??= new \WeakMap();
        if (!isset($this->variantFlags[$product])) {
            $this->variantFlags[$product] = $product->getParentVariantLinks()->count() > 0;
        }

        return $this->variantFlags[$product];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ShoppingListLimitManager::class,
            ShoppingListUrlProvider::class,
            LayoutButtonProvider::class,
            FeatureChecker::class
        ];
    }

    private function getShoppingListLimitManager(): ShoppingListLimitManager
    {
        return $this->container->get(ShoppingListLimitManager::class);
    }

    private function getShoppingListUrlProvider(): ShoppingListUrlProvider
    {
        return $this->container->get(ShoppingListUrlProvider::class);
    }

    private function getLayoutButtonProvider(): LayoutButtonProvider
    {
        return $this->container->get(LayoutButtonProvider::class);
    }

    private function getFeatureChecker(): FeatureChecker
    {
        return $this->container->get(FeatureChecker::class);
    }
}
