<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\VisibilityAwareTestTrait;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CategoryResolvedCacheBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * @group CommunityEdition
 */
class CategoryVisibilityChangeTest extends CategoryCacheTestCase
{
    use VisibilityAwareTestTrait;

    /**
     * @dataProvider visibilityChangeDataProvider
     */
    public function testVisibilityChange(string $categoryReference, array $visibility, array $expectedData): void
    {
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        /** @var CategoryResolvedCacheBuilder $builder */
        $builder = self::getContainer()->get('oro_visibility.visibility.cache.cache_builder');

        $categoryVisibility = $this->getVisibilityEntity($categoryReference, $visibility);
        $originalVisibility = $categoryVisibility->getVisibility();

        $categoryVisibility->setVisibility($visibility['visibility']);
        $this->updateVisibility(self::getContainer()->get('doctrine'), $categoryVisibility);

        $builder->resolveVisibilitySettings($categoryVisibility);

        $this->assertProductVisibilityResolvedCorrect($expectedData);
        $categoryVisibility->setVisibility($originalVisibility);
        $this->updateVisibility(self::getContainer()->get('doctrine'), $categoryVisibility);
    }

    private function getVisibilityEntity(string $categoryReference, array $visibility): VisibilityInterface
    {
        $doctrine = self::getContainer()->get('doctrine');
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        switch ($visibility['type']) {
            case 'all':
                $visibilityEntity = $this->getCategoryVisibility($doctrine, $category);
                $scope = self::getContainer()->get('oro_scope.scope_manager')
                    ->findOrCreate(CategoryVisibility::VISIBILITY_TYPE);
                $visibilityEntity->setScope($scope);
                break;
            case 'customer':
                /** @var Customer $customer */
                $customer = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForCustomer($doctrine, $category, $customer);
                break;
            case 'customerGroup':
                /** @var CustomerGroup $customerGroup */
                $customerGroup = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForCustomerGroup($doctrine, $category, $customerGroup);
                break;
            default:
                throw new \InvalidArgumentException('Unknown visibility type');
        }

        return $visibilityEntity;
    }

    public function visibilityChangeDataProvider(): array
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'expected_visibility_change.yml';

        return Yaml::parse(file_get_contents($file));
    }
}
