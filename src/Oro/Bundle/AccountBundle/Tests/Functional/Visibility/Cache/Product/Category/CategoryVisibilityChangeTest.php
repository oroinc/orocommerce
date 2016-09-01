<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\VisibilityTrait;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class CategoryVisibilityChangeTest extends CategoryCacheTestCase
{
    use VisibilityTrait;

    /**
     * @dataProvider visibilityChangeDataProvider
     *
     * @param string $categoryReference
     * @param array $visibility
     * @param array $expectedData
     */
    public function testVisibilityChange($categoryReference, array $visibility, array $expectedData)
    {
        $this->markTestSkipped('Will be done in scope BB-4124');
        
        $categoryVisibility = $this->getVisibilityEntity($categoryReference, $visibility);

        $originalVisibility = $categoryVisibility->getVisibility();

        $categoryVisibility->setVisibility($visibility['visibility']);
        $this->updateVisibility($this->getContainer()->get('doctrine'), $categoryVisibility);
        $this->assertProductVisibilityResolvedCorrect($expectedData);

        $categoryVisibility->setVisibility($originalVisibility);
        $this->updateVisibility($this->getContainer()->get('doctrine'), $categoryVisibility);
    }

    /**
     * @param $categoryReference
     * @param array $visibility
     * @return \Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface
     */
    protected function getVisibilityEntity($categoryReference, array $visibility)
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        switch ($visibility['type']) {
            case 'all':
                $visibilityEntity = $this->getCategoryVisibility($registry, $category);
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
