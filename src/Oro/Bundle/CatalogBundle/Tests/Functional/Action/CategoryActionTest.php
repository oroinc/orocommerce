<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Action;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryActionTest extends WebTestCase
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
        $operationName = 'oro_catalog_category_delete';
        $entityId = $category->getId();
        $entityClass = $this->getContainer()->getParameter('oro_catalog.entity.category.class');

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

        /** @var CategoryRepository $repo */
        $categoryRepo = $this->getContainer()->get('doctrine')->getRepository(Category::class);
        $organizationRepo = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Organization::class);

        $defaultOrganization = $organizationRepo->getFirst();

        foreach ($removedChildCategories as $removedChildCategory) {
            $this->assertEmpty($categoryRepo->findOneByDefaultTitle($removedChildCategory, $defaultOrganization));
        }
    }

    public function testDeleteRoot()
    {
        $masterCatalog = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCatalogBundle:Category')
            ->getMasterCatalogRoot();

        $this->initClient([], $this->generateBasicAuthHeader());
        $operationName = 'oro_catalog_category_delete';
        $entityId = $masterCatalog->getId();
        $entityClass = $this->getContainer()->getParameter('oro_catalog.entity.category.class');

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

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        $container->get('session')->save();

        return $tokenData;
    }
}
