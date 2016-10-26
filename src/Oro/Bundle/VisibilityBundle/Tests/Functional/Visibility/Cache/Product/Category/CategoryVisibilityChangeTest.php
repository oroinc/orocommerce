<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\VisibilityTrait;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CategoryResolvedCacheBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * @dbIsolation
 */
class CategoryVisibilityChangeTest extends CategoryCacheTestCase
{
    use VisibilityTrait;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    public function setUp()
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
     * @return \Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface
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
            case 'account':
                /** @var Account $account */
                $account = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForAccount($registry, $category, $account);
                break;
            case 'accountGroup':
                /** @var AccountGroup $accountGroup */
                $accountGroup = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForAccountGroup($registry, $category, $accountGroup);
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
