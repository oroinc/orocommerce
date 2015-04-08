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
    }

    /**
     * Test getNotDeletedStatuses
     */
    public function testGetNotDeletedStatuses()
    {
        $this->loadFixtures([
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData'
        ]);

        $statuses = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BRFPBundle:RequestStatus')
            ->getNotDeletedStatuses();

        $this->assertCount(5, $statuses); // 3 from fixtures and 2 defaults

        foreach ($statuses as $status) {
            $this->assertInstanceOf('OroB2B\Bundle\RFPBundle\Entity\RequestStatus', $status);
            $this->assertFalse($status->getDeleted());
        }
    }
}
