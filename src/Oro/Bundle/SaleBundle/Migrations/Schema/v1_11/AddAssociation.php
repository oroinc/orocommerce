<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class AddAssociation implements Migration, PaymentTermExtensionAwareInterface, OrderedMigrationInterface
{
    use PaymentTermExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->paymentTermExtension->addPaymentTermAssociation($schema, 'oro_sale_quote');

        $queries->addPostQuery('UPDATE oro_sale_quote SET payment_term_7c4f1e8e_id = payment_term_id');

        if (class_exists('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource')) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    'Oro\Bundle\CheckoutBundle\Entity\CheckoutSource',
                    'quoteDemand',
                    'datagrid',
                    'show_filter',
                    false
                )
            );
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    'Oro\Bundle\CheckoutBundle\Entity\CheckoutSource',
                    'quoteDemand',
                    'datagrid',
                    'is_visible',
                    DatagridScope::IS_VISIBLE_FALSE
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }
}
