<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;

class AddOrderShippingStatusField implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_order',
            'shippingStatus',
            Order::SHIPPING_STATUS_CODE,
            false,
            false,
            [
                'dataaudit' => ['auditable' => true],
                'enum' => [
                    'immutable_codes' => ExtendHelper::mapToEnumOptionIds(
                        Order::SHIPPING_STATUS_CODE,
                        ['not_shipped', 'shipped']
                    )
                ]
            ]
        );
    }
}
