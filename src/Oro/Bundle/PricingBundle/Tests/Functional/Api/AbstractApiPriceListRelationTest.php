<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListFallback;
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

    /**
     * @return BasePriceListRelation|PriceListFallback
     */
    abstract protected function getFirstRelation();

    /**
     * @return void
     */
    abstract protected function assertFirstRelationMessageSent();

    /**
     * @param string $entityId
     * @param string $associationName
     * @param string $associationId
     */
    protected function assertGetSubResource(
        string $entityId,
        string $associationName,
        string $associationId
    ) {
        $response = $this->getSubresource([
            'entity' => $this->getApiEntityName(),
            'id' => $entityId,
            'association' => $associationName
        ]);

        $result = json_decode($response->getContent(), true);

        self::assertEquals($associationId, $result['data']['id']);
    }

    /**
     * @param string $associationName
     * @param string $associationId
     */
    protected function assertGetSubResourceForFirstRelation(string $associationName, string $associationId)
    {
        $this->assertGetSubResource($this->getFirstRelation()->getId(), $associationName, $associationId);
    }

    /**
     * @param string $associationName
     * @param string $associationClassName
     * @param string $associationId
     */
    protected function assertGetRelationshipForFirstRelation(
        string $associationName,
        string $associationClassName,
        string $associationId
    ) {
        $this->assertGetRelationship(
            $this->getFirstRelation()->getId(),
            $associationName,
            $associationClassName,
            $associationId
        );
    }

    /**
     * @param $entityId
     * @param string $associationName
     * @param string $associationClassName
     * @param string $associationId
     */
    protected function assertGetRelationship(
        string $entityId,
        string $associationName,
        string $associationClassName,
        string $associationId
    ) {
        $response = $this->getRelationship([
            'entity' => $this->getApiEntityName(),
            'id' => $entityId,
            'association' => $associationName
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType($associationClassName),
                    'id' => (string)$associationId
                ]
            ],
            $response
        );
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
        $parameters = $this->getRequestData($this->getAliceFilesFolderName().'/create.yml');
        $this->post($routeParameters, $parameters);
        $response = $this->post($routeParameters, $parameters, [], false);
        static::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        static::assertContains(
            'unique entity constraint',
            $response->getContent()
        );
    }

    public function testDelete()
    {
        $this->cleanScheduledRelationMessages();

        $relationId = $this->getFirstRelation()->getId();

        $this->delete([
            'entity' => $this->getApiEntityName(),
            'id' => $relationId,
        ]);

        $this->assertNull(
            $this->getEntityManager()->find(
                $this->getEntityClass($this->getApiEntityName()),
                $relationId
            )
        );

        $this->assertFirstRelationMessageSent();
    }
}
