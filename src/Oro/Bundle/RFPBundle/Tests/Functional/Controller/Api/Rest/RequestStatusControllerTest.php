<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData;

/**
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

        $this->loadFixtures(['Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestStatusData']);
    }

    public function testDeleteAndRestoreAction()
    {
        $entityManager = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroRFPBundle:RequestStatus');

        $entityRepository = $entityManager->getRepository('OroRFPBundle:RequestStatus');

        $requestStatus = $entityRepository->findOneBy(['name' => LoadRequestStatusData::NAME_NOT_DELETED]);
        $this->assertFalse($requestStatus->getDeleted());

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_rfp_delete_request_status', ['id' => $requestStatus->getId()])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);
        $this->assertNotEmpty($result);
        $this->assertEquals('Request Status deleted', $result['message']);

        $entityManager->clear();

        $requestStatus = $entityRepository->findOneBy(['name' => LoadRequestStatusData::NAME_NOT_DELETED]);
        $this->assertTrue($requestStatus->getDeleted());

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_rfp_restore_request_status', ['id' => $requestStatus->getId()])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);
        $this->assertNotEmpty($result);
        $this->assertEquals('Request Status restored', $result['message']);


        $entityManager->clear();

        $requestStatus = $entityRepository->findOneBy(['name' => LoadRequestStatusData::NAME_NOT_DELETED]);
        $this->assertFalse($requestStatus->getDeleted());
    }
}
