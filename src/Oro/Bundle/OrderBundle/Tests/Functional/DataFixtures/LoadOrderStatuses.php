<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrderStatuses extends AbstractFixture
{
    private const DATA = [
        'open'              => 'Open',
        'cancelled'         => 'Cancelled',
        'closed'            => 'Closed',
        'wait_for_approval' => 'Wait For Approval'
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository(ExtendHelper::buildEnumValueClassName(Order::STATUS_CODE));
        $priority = 1;
        foreach (self::DATA as $id => $name) {
            $enumValue = $enumRepo->createEnumValue($name, $priority++, 'open' === $id, $id);
            $manager->persist($enumValue);
            $this->addReference('order_status.' . $id, $enumValue);
        }
        $manager->flush();
    }
}
