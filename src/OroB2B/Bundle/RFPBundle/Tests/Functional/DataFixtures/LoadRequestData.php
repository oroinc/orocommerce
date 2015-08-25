<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadRequestData extends AbstractFixture implements DependentFixtureInterface
{
    const FIRST_NAME = 'Grzegorz';
    const LAST_NAME = 'Brzeczyszczykiewicz';
    const EMAIL = 'test_request@example.com';

    /**
     * @var array
     */
    protected $requests = [
        [
            'first_name' => self::FIRST_NAME,
            'last_name' => self::LAST_NAME,
            'email' => self::EMAIL,
            'phone' => '2-(999)507-4625',
            'company' => 'Google',
            'role' => 'CEO',
            'body' => 'Hey, you!'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData',
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var RequestStatus $status */
        $status = $manager->getRepository('OroB2BRFPBundle:RequestStatus')->findOneBy([], ['id' => 'ASC']);

        if (!$status) {
            return;
        }

        foreach ($this->requests as $rawRequest) {
            $request = new Request();
            $request
                ->setFirstName($rawRequest['first_name'])
                ->setLastName($rawRequest['last_name'])
                ->setEmail($rawRequest['email'])
                ->setPhone($rawRequest['phone'])
                ->setCompany($rawRequest['company'])
                ->setRole($rawRequest['role'])
                ->setBody($rawRequest['body'])
                ->setStatus($status);

            $manager->persist($request);
        }

        $manager->flush();
    }
}
