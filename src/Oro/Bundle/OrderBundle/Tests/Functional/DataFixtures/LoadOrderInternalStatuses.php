<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Loads order internal statuses.
 */
class LoadOrderInternalStatuses extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EnumOptionRepository $enumRepo */
        $enumRepo = $manager->getRepository(EnumOption::class);
        foreach ($enumRepo->getValues(Order::INTERNAL_STATUS_CODE) as $enumOption) {
            $this->addReference($enumOption->getId(), $enumOption);
        }
    }
}
