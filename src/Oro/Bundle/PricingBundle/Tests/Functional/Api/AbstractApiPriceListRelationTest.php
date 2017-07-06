<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiPriceListRelationTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * @return string
     */
    abstract protected function getApiEntityName(): string;

    /**
     * @return string
     */
    abstract protected function getAliceFilesFolderName(): string;

    abstract protected function getFirstRelation();

    /**
     * @param int $entityId
     * @param string $associationName
     * @param string $associationId
     */
    protected function assertGetSubResource($entityId, $associationName, $associationId)
    {
        $response = $this->getSubresource(
            ['entity' => $this->getApiEntityName(), 'id' => $entityId, 'association' => $associationName]
        );
        $result = json_decode($response->getContent(), true);
        self::assertEquals($associationId, $result['data']['id']);
    }

    public function testGet()
    {
        $relationId = $this->getFirstRelation()->getId();

        $response = $this->get(
            [
                'entity' => $this->getApiEntityName(),
                'id' => $relationId,
            ]
        );

        $this->assertResponseContains($this->getAliceFilesFolderName().'/get.yml', $response);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => $this->getApiEntityName()]);

        $this->assertResponseContains($this->getAliceFilesFolderName().'/get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getApiEntityName()]);
        $parameters = $this->getRequestData($this->getAliceFilesFolderName().'/create_duplicate.yml');
        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', $routeParameters),
            $parameters
        );

        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'unique entity constraint',
            $response->getContent()
        );
    }
}
