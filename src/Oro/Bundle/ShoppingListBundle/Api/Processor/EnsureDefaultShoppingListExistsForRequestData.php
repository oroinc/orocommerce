<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\RequestDataAccessor;
use Oro\Bundle\ShoppingListBundle\Api\DefaultShoppingListFactory;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if the request data contains a default shopping list ID
 * and if this shopping list does not exist, create it and save to the context.
 */
class EnsureDefaultShoppingListExistsForRequestData implements ProcessorInterface
{
    private DefaultShoppingListFactory $defaultShoppingListFactory;
    private RequestDataAccessor $requestDataAccessor;

    public function __construct(
        DefaultShoppingListFactory $defaultShoppingListFactory,
        RequestDataAccessor $requestDataAccessor
    ) {
        $this->defaultShoppingListFactory = $defaultShoppingListFactory;
        $this->requestDataAccessor = $requestDataAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        $notResolvedIdentifierPaths = [];
        $requestData = $context->getRequestData();
        /** @var NotResolvedIdentifier[] $notResolvedIdentifiers */
        $notResolvedIdentifiers = $context->getNotResolvedIdentifiers();
        $requestDataPrefix = 'requestData' . ConfigUtil::PATH_DELIMITER;
        foreach ($notResolvedIdentifiers as $path => $identifier) {
            if (!str_starts_with($path, $requestDataPrefix)) {
                continue;
            }
            if (is_a($identifier->getEntityClass(), ShoppingList::class, true)
                && 'default' === $identifier->getValue()
            ) {
                $path = substr($path, \strlen($requestDataPrefix));
                if (null === $this->requestDataAccessor->getValue($requestData, $path)) {
                    $notResolvedIdentifierPaths[] = $path;
                }
            }
        }
        if (!$notResolvedIdentifierPaths) {
            return;
        }

        $shoppingList = $this->defaultShoppingListFactory->create();
        if (null === $shoppingList) {
            return;
        }

        foreach ($notResolvedIdentifierPaths as $path) {
            $this->requestDataAccessor->setValue($requestData, $path, $shoppingList->getId());
        }
        $context->setRequestData($requestData);
    }
}
