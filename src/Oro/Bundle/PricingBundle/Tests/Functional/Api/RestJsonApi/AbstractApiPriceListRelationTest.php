<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceListFallback;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiPriceListRelationTest extends RestJsonApiTestCase
{
    use MessageQueueExtension;

    abstract protected function getApiEntityName(): string;

    abstract protected function getAliceFilesFolderName(): string;

    /**
     * @return BasePriceListRelation|PriceListFallback
     */
    abstract protected function getFirstRelation();

    abstract protected function assertFirstRelationMessageSent();

    /**
     * {@inheritdoc}
     */
    protected function getRequestDataFolderName(): string
    {
        return parent::getRequestDataFolderName() . DIRECTORY_SEPARATOR . $this->getAliceFilesFolderName();
    }

    /**
     * {@inheritdoc}
     */
    protected function getResponseDataFolderName(): string
    {
        return parent::getResponseDataFolderName() . DIRECTORY_SEPARATOR . $this->getAliceFilesFolderName();
    }

    protected function assertGetSubResource(
        string $entityId,
        string $associationName,
        string $associationId
    ): void {
        $response = $this->getSubresource([
            'entity' => $this->getApiEntityName(),
            'id' => $entityId,
            'association' => $associationName
        ]);

        $result = self::jsonToArray($response->getContent());

        self::assertEquals($associationId, $result['data']['id']);
    }

    protected function assertGetSubResourceForFirstRelation(string $associationName, string $associationId): void
    {
        $this->assertGetSubResource($this->getFirstRelation()->getId(), $associationName, $associationId);
    }

    protected function assertGetRelationshipForFirstRelation(
        string $associationName,
        string $associationClassName,
        string $associationId
    ): void {
        $this->assertGetRelationship(
            $this->getFirstRelation()->getId(),
            $associationName,
            $associationClassName,
            $associationId
        );
    }

    protected function assertGetRelationship(
        string $entityId,
        string $associationName,
        string $associationClassName,
        string $associationId
    ): void {
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

        $this->assertResponseContains('get.yml', $response);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => $this->getApiEntityName()]);

        $this->assertResponseContains('get_list.yml', $response);
    }

    public function testCreateDuplicate()
    {
        $routeParameters = self::processTemplateData(['entity' => $this->getApiEntityName()]);
        $parameters = $this->getRequestData('create.yml');
        $this->post($routeParameters, $parameters);
        $response = $this->post($routeParameters, $parameters, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertStringContainsString('unique entity constraint', $response->getContent());
    }

    public function testDelete()
    {
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
