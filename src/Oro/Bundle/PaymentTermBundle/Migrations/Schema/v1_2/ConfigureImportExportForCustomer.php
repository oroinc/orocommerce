<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ConfigureImportExportForCustomer implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Customer::class,
                'payment_term_7c4f1e8e',
                'importexport',
                'header',
                'Payment term'
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Customer::class,
                'payment_term_7c4f1e8e',
                'importexport',
                'full',
                true
            )
        );
    }
}
