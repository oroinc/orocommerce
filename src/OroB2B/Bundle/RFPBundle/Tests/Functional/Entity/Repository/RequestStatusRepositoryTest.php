<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;

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
}
