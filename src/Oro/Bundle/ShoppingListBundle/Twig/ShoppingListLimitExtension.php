<?php

namespace Oro\Bundle\ShoppingListBundle\Twig;

use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to check if only one shopping list is enabled for the current storefront user:
 *   - is_one_shopping_list_enabled
 */
class ShoppingListLimitExtension extends AbstractExtension
{
    const NAME = 'oro_shopping_list_limit';

    /** @var ShoppingListLimitManager */
    private $shoppingListLimitManager;

    /**
     * @param ShoppingListLimitManager $shoppingListLimitManager
     */
    public function __construct(ShoppingListLimitManager $shoppingListLimitManager)
    {
        $this->shoppingListLimitManager = $shoppingListLimitManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'is_one_shopping_list_enabled',
                [$this->shoppingListLimitManager, 'isOnlyOneEnabled']
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
}
