<?php
declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Migrations\Schema\v4_1_0_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveInvoiceEntityConfig implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        self::removeInvoiceEntityConfig($queries);
    }

    public static function removeInvoiceEntityConfig(QueryBag $queries)
    {
        $classNames = [
            'Oro\Bundle\InvoiceBundle\Entity\Invoice',
            'Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem',
        ];

        foreach ($classNames as $className) {
            if (!class_exists($className, false)) {
                $queries->addQuery(new RemoveTableQuery($className));
            }
        }
    }
}
