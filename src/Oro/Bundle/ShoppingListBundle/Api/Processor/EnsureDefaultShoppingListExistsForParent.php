<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ShoppingListBundle\Api\DefaultShoppingListFactory;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if the parent entity ID represents a default shopping list
 * and if this shopping list does not exist, create it and save to the context.
 */
class EnsureDefaultShoppingListExistsForParent implements ProcessorInterface
{
    private DefaultShoppingListFactory $defaultShoppingListFactory;

    public function __construct(DefaultShoppingListFactory $defaultShoppingListFactory)
    {
        $this->defaultShoppingListFactory = $defaultShoppingListFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        if (null !== $context->getParentId()) {
            return;
        }

        /** @var NotResolvedIdentifier[] $notResolvedIdentifiers */
        $notResolvedIdentifiers = $context->getNotResolvedIdentifiers();
        if (!isset($notResolvedIdentifiers['parentId'])
            || 'default' !== $notResolvedIdentifiers['parentId']->getValue()
        ) {
            return;
        }

        $shoppingList = $this->defaultShoppingListFactory->create();
        if (null === $shoppingList) {
            return;
        }

        $context->setParentEntity($shoppingList);
        $context->setParentId($shoppingList->getId());
    }
}
