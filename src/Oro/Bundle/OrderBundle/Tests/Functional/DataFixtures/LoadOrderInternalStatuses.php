<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;

class LoadOrderInternalStatuses extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository(ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE));
        foreach ($enumRepo->getValues() as $enumValue) {
            $this->addReference('order_internal_status.' . $enumValue->getId(), $enumValue);
        }
    }
}
