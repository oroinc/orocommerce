<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;

class LoadRequestData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $request = new Request();
        $request
            ->setFirstName('John')
            ->setLastName('Dow')
            ->setEmail('test_request@example.com')
            ->setPhone('+17(452)241-1069')
            ->setCompany('SomeCompany')
            ->setRole('SomeManager')
            ->setBody('TestRequestBody');

        $manager->persist($request);
        $manager->flush();
    }
}
