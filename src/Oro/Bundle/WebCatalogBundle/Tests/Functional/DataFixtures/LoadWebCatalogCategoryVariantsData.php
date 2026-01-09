<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadWebCatalogCategoryVariantsData extends AbstractFixture implements DependentFixtureInterface
{
    private static array $data = [
        LoadContentNodesData::CATALOG_1_ROOT => LoadCategoryData::FIRST_LEVEL,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1 => LoadCategoryData::SECOND_LEVEL1,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2 => LoadCategoryData::SECOND_LEVEL2,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1 => LoadCategoryData::THIRD_LEVEL1,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2 => LoadCategoryData::THIRD_LEVEL2,
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadWebCatalogData::class,
            LoadScopeData::class,
            LoadWebsiteData::class,
            LoadContentNodesData::class,
            LoadCategoryData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeData::DEFAULT_SCOPE);

        foreach (LoadContentNodesData::$data as $nodes) {
            foreach ($nodes as $nodeReference => $nodeData) {
                if (!isset(self::$data[$nodeReference])) {
                    continue;
                }

                /** @var Category $category */
                $category = $this->getReference(self::$data[$nodeReference]);

                /** @var ContentNode $node */
                $node = $this->getReference($nodeReference);
                $node->addScope($scope);

                $slug = new Slug();
                $slug->setUrl('/' . $nodeReference);
                $slug->setRouteName('oro_product_frontend_product_index');
                $slug->setRouteParameters(['categoryId' => $category->getId(), 'includeSubcategories' => 1]);
                $slug->addScope($scope);
                $slug->setOrganization($category->getOrganization());
                $manager->persist($slug);

                $variant = new ContentVariant();
                $variant->setType('category_page');
                $variant->setCategoryPageCategory($category);
                $variant->setNode($node);
                $variant->addSlug($slug);
                $manager->persist($variant);
            }
        }

        $manager->flush();
    }
}
