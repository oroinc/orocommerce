<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;
use Oro\Bundle\RFPBundle\Entity\RequestStatus;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData;

/**
 * @dbIsolation
 */
class RequestStatusRepositoryTest extends WebTestCase
{
    /**
     * @var RequestStatusRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroRFPBundle:RequestStatus');

        $this->loadFixtures([
            'Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestWithDeletedStatusData'
        ]);
    }

    /**
     * Test getNotDeletedStatuses
     */
    public function testGetNotDeletedStatuses()
    {
        $statuses = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroRFPBundle:RequestStatus')
            ->getNotDeletedStatuses();

        $this->assertCount(8, $statuses); // 3 from fixtures + 5 default

        foreach ($statuses as $status) {
            $this->assertInstanceOf('Oro\Bundle\RFPBundle\Entity\RequestStatus', $status);
            $this->assertFalse($status->getDeleted());
        }
    }

    /**
     * Test getNotDeletedAndDeletedWithRequestsStatuses
     */
    public function testGetNotDeletedAndDeletedWithRequestsStatuses()
    {
        /** @var RequestStatus[] $statuses */
        $statuses = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroRFPBundle:RequestStatus')
            ->getNotDeletedAndDeletedWithRequestsStatusesQueryBuilder()
            ->getQuery()
            ->getResult();

        $this->assertCount(9, $statuses); // 3 from fixtures + 1 deleted + 5 default

        foreach ($statuses as $status) {
            $this->assertInstanceOf('Oro\Bundle\RFPBundle\Entity\RequestStatus', $status);

            $this->assertEquals($status->getName() === LoadRequestStatusData::NAME_DELETED, $status->getDeleted());
        }
    }
}
