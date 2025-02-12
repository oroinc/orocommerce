<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DeleteDuplicateShoppingListTotals implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery("
            DELETE FROM oro_shopping_list_total
            WHERE is_valid = true
            AND customer_user_id IS NULL
            AND (
                (shopping_list_id, currency) IN (
                    SELECT DISTINCT shopping_list_id, currency
                    FROM oro_shopping_list_total
                    WHERE customer_user_id IS NOT NULL
                      AND is_valid = true
                )
                OR
                id NOT IN (
                    SELECT MAX(id)
                    FROM oro_shopping_list_total
                    WHERE customer_user_id IS NULL
                      AND is_valid = true
                    GROUP BY shopping_list_id, currency
                )
                AND (shopping_list_id, currency) IN (
                    SELECT shopping_list_id, currency
                    FROM oro_shopping_list_total
                    WHERE customer_user_id IS NULL
                      AND is_valid = true
                    GROUP BY shopping_list_id, currency
                    HAVING COUNT(*) > 1
                )
            )
        ");
    }
}
