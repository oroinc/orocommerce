<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestAdditionalNoteData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestAdditionalNoteApiTest extends AbstractRequestApiTest
{
    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestAdditionalNoteData::class]);
    }

    /**
     * @return string
     */
    protected function getEntityClass()
    {
        return RequestAdditionalNote::class;
    }

    /**
     * @return array
     */
    public function cgetParamsAndExpectation()
    {
        $maxCount = LoadRequestData::NUM_REQUESTS * (
            LoadRequestAdditionalNoteData::NUM_CUSTOMER_NOTES + LoadRequestAdditionalNoteData::NUM_SELLER_NOTES
        );

        return [
            [
                'filters' => [],
                'expectedCount' => $maxCount,
                'params' => [],
                'expectedContent' => null,
            ],
        ];
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
        $entityType = $this->getEntityType($this->getEntityClass());

        $response = $this->request(
            $method,
            $this->getUrl($routeName, array_merge(['entity' => $entityType], $param))
        );

        $this->assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @return \Generator
     */
    public function notAllowedActionProvider()
    {
        yield 'create action' => [
            'method' => 'POST',
            'routeName' => 'oro_rest_api_post'
        ];

        yield 'update action' => [
            'method' => 'PATCH',
            'routeName' => 'oro_rest_api_patch',
            'param' => ['id' => 1]
        ];

        yield 'delete action' => [
            'method' => 'DELETE',
            'routeName' => 'oro_rest_api_delete',
            'param' => ['id' => 1]
        ];
    }
}
