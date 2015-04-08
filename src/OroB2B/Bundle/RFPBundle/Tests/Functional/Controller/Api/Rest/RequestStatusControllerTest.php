<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RequestControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        //$this->initClient([], $this->generateWsseAuthHeader());
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BRFPBundle:RequestStatus');
    }

    /**
     * Test restoreAction
     */
    public function testRestoreAction()
    {
        $this->loadFixtures([
            'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData'
        ]);

        $requestStatus = $this->em->getRepository('OroB2BRFPBundle:RequestStatus')->findOneBy(['name' => 'test4']);

        $this->assertTrue($requestStatus->getDeleted());

        $this->client->request('PATCH', $this->getUrl('orob2b_api_rfp_request_status_restore', [
            'id' => $requestStatus->getId()
        ]));

        $requestStatus = $this->em->getRepository('OroB2BRFPBundle:RequestStatus')->findOneBy(['name' => 'test4']);

        $this->assertFalse($requestStatus->getDeleted());
    }
}
