<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadOrganizations extends AbstractFixture
{
    const ORGANIZATION_1 = 'order.simple_organization_1' ;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = new Organization();
        $organization->setName('test organization 1');
        $organization->setEnabled(true);
        $this->addReference(self::ORGANIZATION_1, $organization);
        $manager->persist($organization);
        $manager->flush();
    }
}
