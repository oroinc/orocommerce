<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;

class LoadRequestData extends AbstractFixture
{
    const FIRST_NAME = 'John';
    const LAST_NAME = 'Dow';
    const EMAIL = 'test_request@example.com';

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $status = array_shift($manager->getRepository('OroB2BRFPBundle:RequestStatus')->findBy([], ['id' => 'ASC']));
        if (!$status) {
            return;
        }

        $request = new Request();
        $request
            ->setFirstName(self::FIRST_NAME)
            ->setLastName(self::LAST_NAME)
            ->setEmail(self::EMAIL)
            ->setPhone('+17(452)241-1069')
            ->setCompany('SomeCompany')
            ->setRole('SomeManager')
            ->setBody('TestRequestBody')
            ->setStatus($status);

        $manager->persist($request);
        $manager->flush();
    }
}
