<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_6_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;

/**
 * Remove cascade=['persist'] for taxCode relation of Product, Customer and CustomerGroup entities
 */
class RemoveCascadePersistFromTaxCodeRelations implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateEntityConfigFieldCascadeQuery(
            Product::class,
            ProductTaxCode::class,
            ['taxCode']
        ));

        $queries->addQuery(new UpdateEntityConfigFieldCascadeQuery(
            Customer::class,
            CustomerTaxCode::class,
            ['taxCode']
        ));

        $queries->addQuery(new UpdateEntityConfigFieldCascadeQuery(
            CustomerGroup::class,
            CustomerTaxCode::class,
            ['taxCode']
        ));
    }
}
