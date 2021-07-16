<?php

namespace Oro\Bundle\ShoppingListBundle\Twig;

use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that provides required data for shopping list widget view.
 */
class ShoppingListWidgetExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function getLayoutButtonProvider(): LayoutButtonProvider
    {
        return $this->container->get('oro_action.layout.data_provider.button_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'get_shopping_list_widget_buttons',
                [$this, 'getShoppingListWidgetButtons']
            ),
        ];
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_action.layout.data_provider.button_provider' => LayoutButtonProvider::class,
        ];
    }
}
