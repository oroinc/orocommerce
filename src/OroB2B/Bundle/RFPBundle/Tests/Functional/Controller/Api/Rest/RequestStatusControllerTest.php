<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller\Api\Rest;

use FOS\RestBundle\Util\Codes;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RequestStatusControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData']);
    }

    public function testDeleteAndRestoreAction()
    {
        $entityManager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BRFPBundle:RequestStatus');

        $entityRepository = $entityManager->getRepository('OroB2BRFPBundle:RequestStatus');

        $requestStatus = $entityRepository->findOneBy(['name' => LoadRequestStatusData::NAME_NOT_DELETED]);
        $this->assertFalse($requestStatus->getDeleted());

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_rfp_delete_request_status', ['id' => $requestStatus->getId()])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), Codes::HTTP_OK);
        $this->assertNotEmpty($result);
        $this->assertEquals('Request Status deleted', $result['message']);

        $entityManager->clear();

        $requestStatus = $entityRepository->findOneBy(['name' => LoadRequestStatusData::NAME_NOT_DELETED]);
        $this->assertTrue($requestStatus->getDeleted());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_api_rfp_restore_request_status', ['id' => $requestStatus->getId()])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), Codes::HTTP_OK);
        $this->assertNotEmpty($result);
        $this->assertEquals('Request Status restored', $result['message']);


        $entityManager->clear();

        $requestStatus = $entityRepository->findOneBy(['name' => LoadRequestStatusData::NAME_NOT_DELETED]);
        $this->assertFalse($requestStatus->getDeleted());
    }
}
