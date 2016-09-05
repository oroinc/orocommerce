<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Stub;


use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;

class ProductEntityAliasProviderStub implements EntityClassProviderInterface, EntityAliasProviderInterface
{

    /**
     * {@inheritdoc}
     */
    public function getClassNames()
    {
        return [
            Product::class => [
                'alias' => 'product',
                'plural_alias' => 'products'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAlias($entityClass)
    {
        return new EntityAlias(
            strtolower('product'),
            strtolower('products')
        );
    }
}
