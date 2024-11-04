<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrderStatuses extends AbstractFixture
{
    private const DATA = [
        'open'              => 'Open',
        'cancelled'         => 'Cancelled',
        'closed'            => 'Closed',
        'wait_for_approval' => 'Wait For Approval'
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EnumOptionRepository $enumRepo */
        $enumRepo = $manager->getRepository(EnumOption::class);
        $priority = 1;
        foreach (self::DATA as $id => $name) {
            $enumOption = $enumRepo->createEnumOption(Order::STATUS_CODE, $id, $name, $priority++, 'open' === $id);
            $manager->persist($enumOption);
            $this->addReference($enumOption->getId(), $enumOption);
        }
        $manager->flush();
    }
}
