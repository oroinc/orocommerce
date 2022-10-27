<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\RFPBundle\Entity\Repository\RequestRepository;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestRepositoryTest extends WebTestCase
{
    /**
     * @var RequestRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadRequestData::class
        ]);

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Request::class)
            ->getRepository(Request::class);
    }

    public function testGetRelatedEntitiesCount()
    {
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);

        self::assertSame(4, $this->repository->getRelatedEntitiesCount($customerUser));
    }

    public function testGetRelatedEntitiesCountZero()
    {
        $customerUserWithoutRelatedEntities = $this->getContainer()->get('doctrine')
            ->getManagerForClass(CustomerUser::class)
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);

        self::assertSame(0, $this->repository->getRelatedEntitiesCount($customerUserWithoutRelatedEntities));
    }

    public function testResetCustomerUserForSomeEntities()
    {
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $requestsWithoutCustomerUser = $this->repository->findBy(['customerUser' => null]);
        $this->repository->resetCustomerUser($customerUser, [
            $this->getReference(LoadRequestData::REQUEST2),
            $this->getReference(LoadRequestData::REQUEST7),
        ]);

        $requests = $this->repository->findBy(['customerUser' => null]);
        $this->assertCount(\count($requestsWithoutCustomerUser) + 2, $requests);
    }

    public function testResetCustomerUser()
    {
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);
        $requestsWithoutCustomerUser = $this->repository->findBy(['customerUser' => null]);
        $this->repository->resetCustomerUser($customerUser);

        $requests = $this->repository->findBy(['customerUser' => null]);
        $this->assertCount(\count($requestsWithoutCustomerUser) + 2, $requests);
    }
}
