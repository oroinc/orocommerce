<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestAdditionalNoteData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestAdditionalNoteTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestAdditionalNoteData::class]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'rfqadditionalnotes'],
            ['page' => ['size' => 1000]]
        );

        $expectedCount = LoadRequestData::NUM_REQUESTS
            * (LoadRequestAdditionalNoteData::NUM_CUSTOMER_NOTES + LoadRequestAdditionalNoteData::NUM_SELLER_NOTES);

        self::assertResponseCount($expectedCount, $response);
    }

    public function testGet()
    {
        $entity = $this->getEntityManager()
            ->getRepository(RequestAdditionalNote::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'rfqadditionalnotes', 'id' => $entity->getId()]
        );

        self::assertResponseNotEmpty($response);
    }

    public function testOptionsForList()
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'rfqadditionalnotes']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem()
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'rfqadditionalnotes', 'id' => 1]
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    /**
     * @dataProvider notAllowedActionProvider
     */
    public function testNotAllowedActions(string $method, string $routeName, array $param = [])
    {
        $response = $this->request(
            $method,
            $this->getUrl($routeName, array_merge(['entity' => 'rfqadditionalnotes'], $param))
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function notAllowedActionProvider(): array
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
