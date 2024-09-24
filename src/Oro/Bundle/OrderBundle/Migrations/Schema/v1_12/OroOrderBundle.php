<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Migrations\Data\ORM\LoadOrderInternalStatuses;

class OroOrderBundle implements Migration, OutdatedExtendExtensionAwareInterface
{
    use OutdatedExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addOrderInternalStatusField($schema);
    }

    protected function addOrderInternalStatusField(Schema $schema)
    {
        $internalStatusOptions = new OroOptions();
        $internalStatusOptions->set('enum', 'immutable_codes', LoadOrderInternalStatuses::getDataKeys());

        $internalStatusEnumTable = $this->outdatedExtendExtension->addOutdatedEnumField(
            $schema,
            'oro_order',
            'internal_status',
            Order::INTERNAL_STATUS_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
        $internalStatusEnumTable->addOption(OroOptions::KEY, $internalStatusOptions);
    }
}
