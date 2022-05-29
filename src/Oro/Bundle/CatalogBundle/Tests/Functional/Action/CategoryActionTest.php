<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Action;

use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryActionTest extends WebTestCase
{
    use CatalogTrait;
    use OperationAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryData::class]);
    }

    public function testDelete()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $operationName = 'oro_catalog_category_delete';
        $entityId = $category->getId();
        $entityClass = Category::class;

        $params = $this->getOperationExecuteParams($operationName, $entityId, $entityClass);
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                ]
            ),
            $params,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $removedChildCategories = [
            LoadCategoryData::THIRD_LEVEL1,
            LoadCategoryData::FOURTH_LEVEL1,
        ];

        foreach ($removedChildCategories as $removedChildCategory) {
            $category = $this->findCategory($removedChildCategory);
            $this->assertEmpty($category);
        }
    }

    public function testDeleteRoot()
    {
        $catalog = $this->getRootCategory();
        $this->initClient([], $this->generateBasicAuthHeader());
        $operationName = 'oro_catalog_category_delete';
        $entityId = $catalog->getId();
        $entityClass = Category::class;

        $params = $this->getOperationExecuteParams($operationName, $entityId, $entityClass);
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'oro_catalog_category_delete',
                    'entityId' => $entityId,
                    'entityClass' => $entityClass,
                ]
            ),
            $params,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 403);
    }
}
