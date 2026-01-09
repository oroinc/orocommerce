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
 * Provides a Twig function to get required data for shopping list widget view:
 *   - get_shopping_list_widget_buttons
 */
class ShoppingListExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?ShoppingListLimitManager $shoppingListLimitManager = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('is_one_shopping_list_enabled', [$this, 'isOnlyOneShoppingListEnabled']),
            new TwigFunction('oro_shopping_list_frontend_url', [$this, 'getShoppingListFrontendUrl']),
            new TwigFunction('get_shopping_list_widget_buttons', [$this, 'getShoppingListWidgetButtons']),
            new TwigFunction('get_visible_product', [$this, 'getVisibleProduct']),
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_shopping_list.manager.shopping_list_limit' => ShoppingListLimitManager::class,
            ShoppingListUrlProvider::class,
            'oro_action.layout.data_provider.button_provider' => LayoutButtonProvider::class,
        ];
    }

    public function getVisibleProduct(LineItem $lineItem): Product
    {
        $featureChecker = $this->getFeatureChecker();
        $product = $lineItem->getProduct();
        if (
            $featureChecker->isFeatureEnabled('simple_variations_view_restriction')
            && $product->getParentVariantLinks()->count()
        ) {
            return $lineItem->getParentProduct();
        }

        return $product;
    }

    private function getShoppingListLimitManager(): ShoppingListLimitManager
    {
        if (null === $this->shoppingListLimitManager) {
            $this->shoppingListLimitManager = $this->container->get('oro_shopping_list.manager.shopping_list_limit');
        }

        return $this->shoppingListLimitManager;
    }

    private function getShoppingListUrlProvider(): ShoppingListUrlProvider
    {
        return $this->container->get(ShoppingListUrlProvider::class);
    }

    private function getLayoutButtonProvider(): LayoutButtonProvider
    {
        return $this->container->get('oro_action.layout.data_provider.button_provider');
    }

    private function getFeatureChecker(): FeatureChecker
    {
        return $this->container->get(FeatureChecker::class);
    }
}
