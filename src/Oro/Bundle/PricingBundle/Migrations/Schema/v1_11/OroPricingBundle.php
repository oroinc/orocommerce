<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    #[\Override]
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

    private function replaceCompositePrimaryKey(
        Schema $schema,
        QueryBag $queryBag,
        string $tableName,
        string $oldCompositeIndexName,
        string $newUniqueIndexName,
        array $uniqueConstrainFields
    ): self {
        $table = $schema->getTable($tableName);

        if (DatabasePlatformInterface::DATABASE_POSTGRESQL === $this->connection->getDatabasePlatform()->getName()) {
            $queryBag->addPreQuery("ALTER TABLE $tableName DROP CONSTRAINT $oldCompositeIndexName");
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $queryBag->addPostQuery("ALTER TABLE $tableName ADD PRIMARY KEY (id)");
        } else {
            $queryBag->addPreQuery("ALTER TABLE $tableName DROP PRIMARY KEY");
            $queryBag->addQuery("ALTER TABLE $tableName ADD `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY");
        }

        $table->addUniqueIndex(
            $uniqueConstrainFields,
            $newUniqueIndexName
        );

        return $this;
    }
}
