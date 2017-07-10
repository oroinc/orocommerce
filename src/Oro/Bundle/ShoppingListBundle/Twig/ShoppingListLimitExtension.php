<?php

namespace Oro\Bundle\ShoppingListBundle\Twig;

use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;

class ShoppingListLimitExtension extends \Twig_Extension
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
            new \Twig_SimpleFunction(
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
