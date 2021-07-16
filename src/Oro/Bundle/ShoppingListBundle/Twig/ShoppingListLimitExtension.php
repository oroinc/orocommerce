<?php

namespace Oro\Bundle\ShoppingListBundle\Twig;

use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to check if only one shopping list is enabled for the current storefront user:
 *   - is_one_shopping_list_enabled
 */
class ShoppingListLimitExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    const NAME = 'oro_shopping_list_limit';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'is_one_shopping_list_enabled',
                [$this->container->get('oro_shopping_list.manager.shopping_list_limit'), 'isOnlyOneEnabled']
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_shopping_list.manager.shopping_list_limit' => ShoppingListLimitManager::class,
        ];
    }
}
