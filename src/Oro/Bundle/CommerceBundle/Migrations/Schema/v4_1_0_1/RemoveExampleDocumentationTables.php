<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Migrations\Schema\v4_1_0_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveExampleDocumentationTables implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $classNames = [
            'ACME\Bundle\WysiwygBundle\Entity\BlogPost',
            'ACME\Bundle\CollectOnDeliveryBundle\Entity\CollectOnDeliverySettings',
            'ACME\Bundle\FastShippingBundle\Entity\FastShippingSettings'
        ];
        foreach ($classNames as $className) {
            if (!class_exists($className, false)) {
                $queries->addQuery(new RemoveTableQuery($className));
            }
        }
    }
}
