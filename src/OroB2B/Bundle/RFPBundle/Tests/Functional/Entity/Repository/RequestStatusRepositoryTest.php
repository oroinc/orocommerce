<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;

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
        $this->repository = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BRFPBundle:RequestStatus');

        $this->loadFixtures([
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestWithDeletedStatusData'
        ]);
    }

    /**
     * Test getNotDeletedStatuses
     */
    public function testGetNotDeletedStatuses()
    {
        $statuses = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BRFPBundle:RequestStatus')
            ->getNotDeletedStatuses();

        $this->assertCount(5, $statuses); // 3 from fixtures + 2 deafult

        foreach ($statuses as $status) {
            $this->assertInstanceOf('OroB2B\Bundle\RFPBundle\Entity\RequestStatus', $status);
            $this->assertFalse($status->getDeleted());
        }
    }

    /**
     * Test getNotDeletedAndDeletedWithRequestsStatuses
     */
    public function testGetNotDeletedAndDeletedWithRequestsStatuses()
    {
        $statuses = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BRFPBundle:RequestStatus')
            ->getNotDeletedAndDeletedWithRequestsStatuses();

        $this->assertCount(6, $statuses); // 3 from fixtures + 1 deleted + 2 deafult

        foreach ($statuses as $status) {
            $this->assertInstanceOf('OroB2B\Bundle\RFPBundle\Entity\RequestStatus', $status);

            if ($status->getName() == 'deleted') {
                $this->assertTrue($status->getDeleted());
            } else {
                $this->assertFalse($status->getDeleted());
            }
        }
    }
}
