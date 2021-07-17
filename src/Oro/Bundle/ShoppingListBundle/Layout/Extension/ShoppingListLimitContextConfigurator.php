<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\Extension;

use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Add data about shopping list limit to the layout context
 */
class ShoppingListLimitContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * @var ShoppingListLimitManager
     */
    private $limitManager;

    public function __construct(ShoppingListLimitManager $limitManager)
    {
        $this->limitManager = $limitManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $value = $this->limitManager->isOnlyOneEnabled();

        $context->getResolver()->setDefault('isSingleShoppingList', false);
        $context->set('isSingleShoppingList', $value);
    }
}
