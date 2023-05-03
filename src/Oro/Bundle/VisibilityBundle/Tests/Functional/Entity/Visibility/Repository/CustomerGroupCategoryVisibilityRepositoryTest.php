<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerGroupCategoryVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

class CustomerGroupCategoryVisibilityRepositoryTest extends WebTestCase
{
    private CustomerGroupCategoryVisibilityRepository $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerGroupCategoryVisibility::class);

        $this->loadFixtures([
            LoadGroups::class,
            LoadCategoryData::class,
            LoadCategoryVisibilityData::class
        ]);
    }

    /**
     * @dataProvider getCategoryCustomerGroupIdsByVisibilityDataProvider
     */
    public function testGetCategoryCustomerGroupIdsByVisibility(
        string $categoryName,
        string $visibility,
        array $expectedCustomerGroups,
        array $restricted = null
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $customerGroupIds = $this->repository->getCategoryCustomerGroupIdsByVisibility(
            $category,
            $visibility,
            $restricted
        );

        $expectedCustomerGroupIds = [];
        foreach ($expectedCustomerGroups as $expectedCustomerGroupName) {
            $customerGroup = $this->getReference($expectedCustomerGroupName);
            $expectedCustomerGroupIds[] = $customerGroup->getId();
        }

        sort($expectedCustomerGroupIds);
        sort($customerGroupIds);

        $this->assertEquals($expectedCustomerGroupIds, $customerGroupIds);
    }

    public function getCategoryCustomerGroupIdsByVisibilityDataProvider(): array
    {
        return [
            'FIRST_LEVEL with HIDDEN' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => CustomerGroupCategoryVisibility::HIDDEN,
                'expectedCustomerGroups' => [
                    'customer_group.group1'
                ]
            ],
            'FIRST_LEVEL with VISIBLE restricted' => [
                'categoryName' => LoadCategoryData::FIRST_LEVEL,
                'visibility' => CustomerGroupCategoryVisibility::VISIBLE,
                'expectedCustomerGroups' => [],
                'restricted' => []
            ],
            'FOURTH_LEVEL1 with PARENT_CATEGORY' => [
                'categoryName' => LoadCategoryData::FOURTH_LEVEL1,
                'visibility' => CustomerGroupCategoryVisibility::PARENT_CATEGORY,
                'expectedCustomerGroups' => [
                    'customer_group.group1',
                    'customer_group.group3',
                    'customer_group.anonymous'
                ]
            ],
        ];
    }
}
