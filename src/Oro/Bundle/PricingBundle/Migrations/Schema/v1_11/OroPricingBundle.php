<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;

use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration
{
    /**
     * {@inheritDoc}
     *
     * @throws SchemaException If the index does not exist.
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this
            ->replaceCompositePrimaryKey(
                $schema,
                $queries,
                'oro_price_list_to_customer',
                'oro_price_list_to_customer_pkey',
                'oro_price_list_to_customer_unique_key',
                ['customer_id', 'price_list_id', 'website_id']
            )
            ->replaceCompositePrimaryKey(
                $schema,
                $queries,
                'oro_price_list_to_cus_group',
                'oro_price_list_to_cus_group_pkey',
                'oro_price_list_to_cus_group_unique_key',
                ['customer_group_id', 'price_list_id', 'website_id']
            )
            ->replaceCompositePrimaryKey(
                $schema,
                $queries,
                'oro_price_list_to_website',
                'oro_price_list_to_website_pkey',
                'oro_price_list_to_website_unique_key',
                ['price_list_id', 'website_id']
            );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queryBag
     * @param string $tableName
     * @param string $oldCompositeIndexName
     * @param string $newUniqueIndexName
     * @param array $uniqueConstrainFields
     *
     * @return OroPricingBundle
     */
    private function replaceCompositePrimaryKey(
        Schema $schema,
        QueryBag $queryBag,
        string $tableName,
        string $oldCompositeIndexName,
        string $newUniqueIndexName,
        array $uniqueConstrainFields
    ): self
    {
        $table = $schema->getTable($tableName);

        $queryBag->addPreQuery("ALTER TABLE $tableName DROP CONSTRAINT $oldCompositeIndexName");

        $table->addColumn('id', 'integer', ['autoincrement' => true]);

        $table->addUniqueIndex(
            $uniqueConstrainFields,
            $newUniqueIndexName
        );

        $queryBag->addPostQuery("ALTER TABLE $tableName ADD PRIMARY KEY (id)");

        return $this;
    }
}
