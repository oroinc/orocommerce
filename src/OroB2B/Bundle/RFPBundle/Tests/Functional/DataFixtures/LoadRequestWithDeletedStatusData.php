<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadRequestWithDeletedStatusData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $requests = [
        [
            'first_name' => 'Grzegorz',
            'last_name'  => 'Brzeczyszczykiewicz',
            'email'      => 'daddy@google.com',
            'phone'      => '2-(999)507-4625',
            'company'    => 'Google',
            'role'       => 'CEO',
            'body'       => 'Hey, you!'
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $om)
    {
        $deletedRequestStatus = $om->getRepository('OroB2BRFPBundle:RequestStatus')
            ->findOneBy(['name' => LoadRequestStatusData::NAME_DELETED]);

        if (!$deletedRequestStatus instanceof RequestStatus) {
            throw new \LogicException('$deletedRequestStatus must be instance of RequestStatus');
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
                ->setStatus($deletedRequestStatus)
            ;

            $om->persist($request);
        }

        $om->flush();
    }
}
