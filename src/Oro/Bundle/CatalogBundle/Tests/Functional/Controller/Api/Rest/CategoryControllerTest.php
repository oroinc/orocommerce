<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadCategoryData::class
            ]
        );
    }

    public function testDelete()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'oro_catalog_category_delete',
                    'entityId[id]' => $category->getId(),
                    'entityClass' => $this->getContainer()->getParameter('oro_catalog.entity.category.class'),
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $removedChildCategories = [
            LoadCategoryData::THIRD_LEVEL1,
            LoadCategoryData::FOURTH_LEVEL1,
        ];

        /** @var CategoryRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository('OroCatalogBundle:Category');

        foreach ($removedChildCategories as $removedChildCategory) {
            $this->assertEmpty($repo->findOneByDefaultTitle($removedChildCategory));
        }
    }

    public function testDeleteRoot()
    {
        $masterCatalog = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCatalogBundle:Category')
            ->getMasterCatalogRoot();

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'oro_catalog_category_delete',
                    'entityId[id]' => $masterCatalog->getId(),
                    'entityClass' => $this->getContainer()->getParameter('oro_catalog.entity.category.class'),
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $result = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($result, 403);
    }
}
