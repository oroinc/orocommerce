<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_10_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\UpdateOwnershipTypeQuery;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Change CASCADE operation for customer user relation to SET NULL for Shopping List and Line Item
 * Add customer relation information to ownership metadata
 */
class AddCustomerToShoppingListOwnershipMetadata implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeCustomerUserCascadeToSetNull($schema, $schema->getTable('oro_shopping_list'));
        $this->changeCustomerUserCascadeToSetNull($schema, $schema->getTable('oro_shopping_list_line_item'));

        $this->addCustomerToOwnershipMetadata($queries, ShoppingList::class);
    }

    private function changeCustomerUserCascadeToSetNull(Schema $schema, Table $table, ?string $constraintName = null)
    {
        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getLocalColumns() === ['customer_user_id']) {
                $table->removeForeignKey($foreignKey->getName());
                $table->addForeignKeyConstraint(
                    $schema->getTable('oro_customer_user'),
                    ['customer_user_id'],
                    ['id'],
                    ['onDelete' => 'SET NULL', 'onUpdate' => null],
                    $constraintName
                );
                break;
            }
        }
    }

    private function addCustomerToOwnershipMetadata(QueryBag $queries, string $className): void
    {
        $queries->addQuery(
            new UpdateOwnershipTypeQuery(
                $className,
                [
                    'frontend_customer_field_name' => 'customer',
                    'frontend_customer_column_name' => 'customer_id'
                ]
            )
        );
    }
}
