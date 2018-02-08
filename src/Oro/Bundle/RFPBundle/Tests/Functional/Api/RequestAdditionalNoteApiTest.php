<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestAdditionalNoteData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestAdditionalNoteApiTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestAdditionalNoteData::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'requestadditionalnotes'],
            ['page' => ['size' => 1000]]
        );

        $expectedCount = LoadRequestData::NUM_REQUESTS
            * (LoadRequestAdditionalNoteData::NUM_CUSTOMER_NOTES + LoadRequestAdditionalNoteData::NUM_SELLER_NOTES);

        $this->assertResponseCount($expectedCount, $response);
    }

    public function testGet()
    {
        $entity = $this->getEntityManager()
            ->getRepository(RequestAdditionalNote::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'requestadditionalnotes', 'id' => $entity->getId()]
        );

        $this->assertResponseNotEmpty($response);
    }

    /**
     * @dataProvider notAllowedActionProvider
     *
     * @param string $method
     * @param string $routeName
     * @param array $param
     */
    public function testNotAllowedActions($method, $routeName, array $param = [])
    {
        $response = $this->request(
            $method,
            $this->getUrl($routeName, array_merge(['entity' => 'requestadditionalnotes'], $param))
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
        self::assertEquals('GET', $response->headers->get('Allow'));
    }

    /**
     * @return array
     */
    public function notAllowedActionProvider()
    {
        return [
            'create action' => [
                'method' => 'POST',
                'routeName' => 'oro_rest_api_list'
            ],
            'update action' => [
                'method' => 'PATCH',
                'routeName' => 'oro_rest_api_item',
                'param' => ['id' => 1]
            ],
            'delete action' => [
                'method' => 'DELETE',
                'routeName' => 'oro_rest_api_item',
                'param' => ['id' => 1]
            ],
        ];
    }
}
