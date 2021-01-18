<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Psr\Log\LoggerInterface;

class ConvertCheckoutLineItemsDataQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $queries = [];
        $rows = $this->getCheckouts($logger);

        foreach ($rows as $row) {
            if ($row['shoppinglist_id']) {
                $queries[] = $this->getConvertFromShoppingListQuery($row['id'], $row['shoppinglist_id']);
                continue;
            }
            if ($row['quotedemand_id']) {
                $queries[] = $this->getConvertFromQuoteDemandQuery($row['id'], $row['quotedemand_id']);
                continue;
            }
        }

        // execute update queries
        foreach ($queries as $val) {
            $this->logQuery($logger, $val[0], $val[1], $val[2]);
            if (!$dryRun) {
                $this->connection->executeStatement($val[0], $val[1], $val[2]);
            }
        }
    }

    /**
     * @param int $checkoutId
     * @param int $shoppingListId
     * @return array
     */
    protected function getConvertFromShoppingListQuery($checkoutId, $shoppingListId)
    {
        $lineItemFields = [
            'checkout_id',
            'product_id',
            'parent_product_id',
            'product_unit_id',
            'product_sku',
            'quantity',
            'product_unit_code',
            'price_type',
            'comment',
            'from_external_source',
            'is_price_fixed',
        ];

        $shoppingListLineItemFields =  [
            ':checkout_id',
            'sl.product_id',
            'sl.parent_product_id',
            'sl.unit_code',
            'p.sku',
            'sl.quantity',
            'sl.unit_code',
            PriceTypeAwareInterface::PRICE_TYPE_UNIT,
            'sl.notes',
            'false',
            'false',
        ];

        $sql = sprintf(
            'INSERT INTO oro_checkout_line_item (%s) 
             SELECT %s FROM oro_shopping_list_line_item sl 
             JOIN oro_product p ON sl.product_id = p.id
             WHERE sl.shopping_list_id = :shoppinglist_id',
            implode(', ', $lineItemFields),
            implode(', ', $shoppingListLineItemFields)
        );

        return [
            $sql,
            ['checkout_id' => $checkoutId, 'shoppinglist_id' => $shoppingListId],
            ['checkout_id' => Types::INTEGER, 'shoppinglist_id' => Types::INTEGER]
        ];
    }

    /**
     * @param int $checkoutId
     * @param int $quoteDemandId
     * @return array
     */
    protected function getConvertFromQuoteDemandQuery($checkoutId, $quoteDemandId)
    {
        $lineItemFields = [
            'checkout_id',
            'product_id',
            'product_unit_id',
            'product_sku',
            'quantity',
            'product_unit_code',
            'free_form_product',
            'currency',
            'value',
            'price_type',
            'comment',
            'from_external_source',
            'is_price_fixed',
        ];

        $quoteDemandLineItemFields =  [
            ':checkout_id',
            'qp.product_id',
            'qpo.product_unit_id',
            'qp.product_sku',
            'qpd.quantity',
            'qpo.product_unit_code',
            'qp.free_form_product',
            'qpo.currency',
            'qpo.value',
            'qpo.price_type',
            'qp.comment',
            'true',
            'true',
        ];

        $sql = sprintf(
            'INSERT INTO oro_checkout_line_item (%s) 
             SELECT %s FROM oro_quote_product_demand qpd 
             JOIN oro_sale_quote_prod_offer qpo ON qpd.quote_product_offer_id = qpo.id
             JOIN oro_sale_quote_product qp ON qpo.quote_product_id = qp.id
             WHERE qpd.quote_demand_id = :quotedemand_id',
            implode(', ', $lineItemFields),
            implode(', ', $quoteDemandLineItemFields)
        );

        return [
            $sql,
            ['checkout_id' => $checkoutId, 'quotedemand_id' => $quoteDemandId],
            ['checkout_id' => Types::INTEGER, 'quotedemand_id' => Types::INTEGER]
        ];
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getCheckouts(LoggerInterface $logger)
    {
        $sql = 'SELECT c.id, cs.shoppinglist_id, cs.quotedemand_id
                FROM oro_checkout c
                JOIN oro_checkout_source cs ON c.source_id = cs.id';
        $params = ['class' => Checkout::class];
        $types  = ['class' => 'string'];

        $this->logQuery($logger, $sql, $params, $types);

        return $this->connection->fetchAll($sql, $params, $types);
    }
}
