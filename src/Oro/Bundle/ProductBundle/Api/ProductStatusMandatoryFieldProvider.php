<?php

namespace Oro\Bundle\ProductBundle\Api;

use Oro\Bundle\ApiBundle\Util\MandatoryFieldProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Adds the product status field to SELECT clause for all product queries.
 * As "partial" is used API queries, the product status is required to correct work of ProductStatusVoter.
 * @see \Oro\Bundle\ProductBundle\Acl\Voter\ProductStatusVoter
 * This class can be removed if EntitySerializer component will be reimplemented
 * to use DBAL instead of ORM (BAP-10066).
 */
class ProductStatusMandatoryFieldProvider implements MandatoryFieldProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMandatoryFields(string $entityClass): array
    {
        return Product::class === $entityClass
            ? ['status']
            : [];
    }
}
