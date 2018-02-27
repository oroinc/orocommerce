<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Migrations\Schema\OroPaymentTermBundleInstaller;

class OroPaymentTermBundle extends OroPaymentTermBundleInstaller implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->migrateRelations($schema, $queries);
    }
}
