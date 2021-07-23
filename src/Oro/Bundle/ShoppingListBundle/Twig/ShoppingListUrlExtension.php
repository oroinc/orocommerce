<?php

namespace Oro\Bundle\ShoppingListBundle\Twig;

use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get shopping list storefront urls:
 *   - shopping_list_frontend_url
 */
class ShoppingListUrlExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'oro_shopping_list_frontend_url',
                [$this->container->get(ShoppingListUrlProvider::class), 'getFrontendUrl']
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            ShoppingListUrlProvider::class,
        ];
    }
}
