<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\RequestAdditionalNote;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestAdditionalNoteData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

class RequestAdditionalNoteTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestAdditionalNoteData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'rfqadditionalnotes'],
            ['page' => ['size' => 1000]]
        );

        $expectedCount = LoadRequestData::NUM_REQUESTS
            * (LoadRequestAdditionalNoteData::NUM_CUSTOMER_NOTES + LoadRequestAdditionalNoteData::NUM_SELLER_NOTES);

        self::assertResponseCount($expectedCount, $response);
    }

    public function testGet(): void
    {
        $entity = $this->getEntityManager()
            ->getRepository(RequestAdditionalNote::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'rfqadditionalnotes', 'id' => $entity->getId()]
        );

        self::assertResponseNotEmpty($response);
    }

    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'rfqadditionalnotes']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'rfqadditionalnotes', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'rfqadditionalnotes'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'rfqadditionalnotes', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'rfqadditionalnotes', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'rfqadditionalnotes'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
