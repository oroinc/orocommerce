<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\VisibilityTrait;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CategoryResolvedCacheBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * @group CommunityEdition
 */
class CategoryVisibilityChangeTest extends CategoryCacheTestCase
{
    use VisibilityTrait;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
    }

    /**
     * @dataProvider visibilityChangeDataProvider
     *
     * @param string $categoryReference
     * @param array $visibility
     * @param array $expectedData
     */
    public function testVisibilityChange($categoryReference, array $visibility, array $expectedData)
    {
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        /** @var Registry $registry */
        $registry = $this->getContainer()->get('doctrine');
        /** @var CategoryResolvedCacheBuilder $builder */
        $builder = $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder');

        $categoryVisibility = $this->getVisibilityEntity($categoryReference, $visibility);
        $originalVisibility = $categoryVisibility->getVisibility();

        $categoryVisibility->setVisibility($visibility['visibility']);
        $this->updateVisibility($registry, $categoryVisibility);

        $builder->resolveVisibilitySettings($categoryVisibility);

        $this->assertProductVisibilityResolvedCorrect($expectedData);
        $categoryVisibility->setVisibility($originalVisibility);
        $this->updateVisibility($this->getContainer()->get('doctrine'), $categoryVisibility);
    }

    /**
     * @param $categoryReference
     * @param array $visibility
     * @return VisibilityInterface
     */
    protected function getVisibilityEntity($categoryReference, array $visibility)
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        switch ($visibility['type']) {
            case 'all':
                $visibilityEntity = $this->getCategoryVisibility($registry, $category);
                $scope = $this->scopeManager->findOrCreate(CategoryVisibility::VISIBILITY_TYPE);
                $visibilityEntity->setScope($scope);
                break;
            case 'customer':
                /** @var Customer $customer */
                $customer = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForCustomer($registry, $category, $customer);
                break;
            case 'customerGroup':
                /** @var CustomerGroup $customerGroup */
                $customerGroup = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForCustomerGroup($registry, $category, $customerGroup);
                break;
            default:
                throw new \InvalidArgumentException('Unknown visibility type');
        }

        return $visibilityEntity;
    }

    /**
     * @return array
     */
    public function visibilityChangeDataProvider()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'expected_visibility_change.yml';

        return Yaml::parse(file_get_contents($file));
    }
}
