<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\MessageQueueTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityRepository;

abstract class AbstractApiPriceListRelationTest extends RestJsonApiTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                LoadPriceListRelations::class,
            ]
        );
    }

    /**
     * @return string
     */
    abstract protected function getApiEntityName(): string;

    /**
     * @return string
     */
    abstract protected function getAliceFilesFolderName(): string;

    /**
     * @return EntityRepository
     */
    abstract protected function getEntityRepository(): EntityRepository;

    /**
     * @param BasePriceListRelation $entity
     *
     * @return array
     */
    abstract protected function prepareRebuildPriceListMessagesForEntity(BasePriceListRelation $entity): array;

    /**
     * @return array
     */
    abstract protected function getDeleteListFilter();

    /**
     * @return array
     */
    abstract protected function getExpectedRebuildMessagesOnDeleteList(): array;

    /**
     * @return BasePriceListRelation
     */
    abstract protected function getFirstRelation(): BasePriceListRelation;

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

    public function testCreate()
    {
        $response = $this->post(
            ['entity' => $this->getApiEntityName()],
            $this->getAliceFilesFolderName().'/create.yml'
        );

        $this->assertResponseContains(
            sprintf('../requests/%s/create.yml', $this->getAliceFilesFolderName()),
            $response
        );
    }

    public function testDeleteList()
    {
        $this->cleanScheduledRelationMessages();

        $messages = $this->getExpectedRebuildMessagesOnDeleteList();

        $this->cdelete(
            ['entity' => $this->getApiEntityName()],
            [
                'filter' => $this->getDeleteListFilter(),
            ]
        );

        foreach ($messages as $message) {
            static::assertMessageSent(Topics::REBUILD_COMBINED_PRICE_LISTS, $message);
        }
    }

    public function testDelete()
    {
        $this->cleanScheduledRelationMessages();

        $relation = $this->getFirstRelation();
        $id = $relation->getId();

        $expectedMessage = $this->prepareRebuildPriceListMessagesForEntity($relation);

        $this->delete(
            [
                'entity' => $this->getApiEntityName(),
                'id' => $id,
            ]
        );

        $this->assertNull(
            $this->getEntityRepository()->find($id)
        );

        static::assertMessageSent(Topics::REBUILD_COMBINED_PRICE_LISTS, $expectedMessage);
    }

    public function testUpdate()
    {
        $this->cleanScheduledRelationMessages();

        $relation = $this->getFirstRelation();
        $id = $relation->getId();

        $expectedMessage = $this->prepareRebuildPriceListMessagesForEntity($relation);
        $expectedMergedAllowed = !$relation->isMergeAllowed();
        $expectedSortOrder = 999;

        $this->patch(
            ['entity' => $this->getApiEntityName(), 'id' => (string)$id],
            [
                'data' => [
                    'id' => (string)$id,
                    'type' => $this->getApiEntityName(),
                    'attributes' => [
                        'sortOrder' => $expectedSortOrder,
                        'mergeAllowed' => $expectedMergedAllowed,
                    ],
                ],
            ]
        );

        $updatedRelation = $this->getEntityRepository()->find($id);

        static::assertSame($expectedSortOrder, $updatedRelation->getSortOrder());
        static::assertSame($expectedMergedAllowed, $updatedRelation->isMergeAllowed());
        static::assertMessageSent(Topics::REBUILD_COMBINED_PRICE_LISTS, $expectedMessage);
    }

    public function testGetSubResources()
    {
        $relation = $this->getFirstRelation();

        $this->assertGetSubResource($relation->getId(), 'priceList', $relation->getPriceList()->getId());
        $this->assertGetSubResource($relation->getId(), 'website', $relation->getWebsite()->getId());
    }

    public function testGetRelationships()
    {
        $relation = $this->getFirstRelation();

        $response = $this->getRelationship(
            [
                'entity' => $this->getApiEntityName(),
                'id' => $relation->getId(),
                'association' => 'priceList',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(PriceList::class),
                    'id' => (string)$relation->getPriceList()->getId(),
                ],
            ],
            $response
        );

        $response = $this->getRelationship(
            [
                'entity' => $this->getApiEntityName(),
                'id' => $relation->getId(),
                'association' => 'website',
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $this->getEntityType(Website::class),
                    'id' => (string)$relation->getWebsite()->getId(),
                ],
            ],
            $response
        );
    }
}
