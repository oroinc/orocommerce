<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Migrations\Data\ORM\LoadOrderInternalStatuses;

class OroOrderBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    private $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addOrderInternalStatusField($schema);
    }

    protected function addOrderInternalStatusField(Schema $schema)
    {
        $internalStatusOptions = new OroOptions();
        $internalStatusOptions->set('enum', 'immutable_codes', LoadOrderInternalStatuses::getDataKeys());

        $internalStatusEnumTable = $this->extendExtension->addEnumField(
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
