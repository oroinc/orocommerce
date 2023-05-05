<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\EntityIdResolverInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value for "default" field for a shopping list.
 */
class ComputeShoppingListDefault implements ProcessorInterface
{
    private const FIELD_NAME = 'default';

    private EntityIdResolverInterface $defaultShoppingListEntityIdResolver;

    public function __construct(EntityIdResolverInterface $defaultShoppingListEntityIdResolver)
    {
        $this->defaultShoppingListEntityIdResolver = $defaultShoppingListEntityIdResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        if (!$context->isFieldRequestedForCollection(self::FIELD_NAME, $data)) {
            return;
        }

        $idFieldName = $context->getResultFieldName('id');
        $currentShoppingListId = $this->defaultShoppingListEntityIdResolver->resolve();
        foreach ($data as $key => $item) {
            $data[$key][self::FIELD_NAME] =
                null !== $currentShoppingListId
                && $currentShoppingListId === $item[$idFieldName];
        }

        $context->setData($data);
    }
}
