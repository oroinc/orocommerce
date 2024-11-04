<?php

namespace Oro\Bundle\OrderBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;

/**
 * Updates default status for Order entity.
 */
class UpdateDefaultOrderStatuses extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrderInternalStatuses::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $defaultStatusId = ExtendHelper::buildEnumOptionId(
            Order::INTERNAL_STATUS_CODE,
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
        );
        $sql = "UPDATE oro_order
        set serialized_data = jsonb_set(serialized_data::jsonb, '{internal_status}', :status)
        WHERE serialized_data::json -> 'internal_status' is null";

        $manager->getConnection()->executeQuery(
            $sql,
            ['status' => '"' . $defaultStatusId . '"']
        );
    }
}
