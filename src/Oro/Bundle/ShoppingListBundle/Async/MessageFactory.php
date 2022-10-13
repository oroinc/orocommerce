<?php

namespace Oro\Bundle\ShoppingListBundle\Async;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Create/read MQ messages for shopping list line items totals actualization.
 */
class MessageFactory
{
    protected const CONTEXT_KEY = 'context';
    protected const CLASS_KEY = 'class';
    protected const PRODUCTS_KEY = 'products';
    protected const ID_KEY = 'id';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function getContext(array $data): ?object
    {
        if (isset($data[self::CONTEXT_KEY])) {
            $context = $data[self::CONTEXT_KEY];
            return $this->doctrineHelper->getEntity($context[self::CLASS_KEY], $context[self::ID_KEY]);
        }

        return null;
    }

    public function getProductIds(array $data): array
    {
        return $data[self::PRODUCTS_KEY] ?? [];
    }

    public function createShoppingTotalsInvalidateMessage(?object $context, array $productIds = []): array
    {
        $data = [self::PRODUCTS_KEY => $productIds];
        if ($context) {
            $data[self::CONTEXT_KEY] = [
                self::CLASS_KEY => $this->doctrineHelper->getEntityClass($context),
                self::ID_KEY => $this->doctrineHelper->getSingleEntityIdentifier($context, false)
            ];
        }

        return $data;
    }

    public function createShoppingListTotalsInvalidateMessageForConfigScope(string $scope, int|string $id): array
    {
        if ($scope === 'website') {
            return [self::CONTEXT_KEY => [self::CLASS_KEY => Website::class, self::ID_KEY => $id]];
        }

        return [];
    }
}
