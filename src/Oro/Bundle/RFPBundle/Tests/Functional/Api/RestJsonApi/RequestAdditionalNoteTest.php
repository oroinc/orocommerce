<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestAdditionalNoteData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestAdditionalNoteTest extends RestJsonApiTestCase
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

    public function testOptionsForList()
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'requestadditionalnotes']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem()
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'requestadditionalnotes', 'id' => 1]
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
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
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    /**
     * @return array
     */
    public function notAllowedActionProvider()
    {
        return [
            'create action' => [
                'method' => 'POST',
                'routeName' => $this->getListRouteName()
            ],
            'update action' => [
                'method' => 'PATCH',
                'routeName' => $this->getItemRouteName(),
                'param' => ['id' => 1]
            ],
            'delete action' => [
                'method' => 'DELETE',
                'routeName' => $this->getItemRouteName(),
                'param' => ['id' => 1]
            ],
        ];
    }
}
