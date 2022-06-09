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
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;

class LoadWebCatalogCategoryVariantsData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    public static $data = [
        LoadContentNodesData::CATALOG_1_ROOT => LoadCategoryData::FIRST_LEVEL,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1 => LoadCategoryData::SECOND_LEVEL1,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2 => LoadCategoryData::SECOND_LEVEL2,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1 => LoadCategoryData::THIRD_LEVEL1,
        LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2 => LoadCategoryData::THIRD_LEVEL2,
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeData::DEFAULT_SCOPE);

        foreach (LoadContentNodesData::$data as $nodes) {
            foreach ($nodes as $nodeReference => $nodeData) {
                if (!isset(self::$data[$nodeReference])) {
                    unset($nodeData);
                    continue;
                }
                /** @var Category $category */
                $category = $this->getReference(self::$data[$nodeReference]);

                $node = $this->getReference($nodeReference);
                $node->addScope($scope);

                $slug = new Slug();
                $slug->setUrl('/'.$nodeReference);
                $slug->setRouteName($this->getRoute());
                $slug->setRouteParameters(['categoryId' => $category->getId(), 'includeSubcategories'=>1]);
                $slug->addScope($scope);
                $slug->setOrganization($category->getOrganization());

                $manager->persist($slug);

                $variant = new ContentVariant();
                $variant->setType($this->getContentVariantType());
                $variant->setCategoryPageCategory($category);
                $variant->setNode($node);
                $variant->addSlug($slug);

                $manager->persist($variant);
            }
        }

        $manager->flush();
    }

    /**
     * @inheritdoc
     */
    public function getDependencies()
    {
        return [
            LoadWebCatalogData::class,
            LoadContentNodesData::class,
            LoadCategoryData::class,
            LoadScopeData::class,
            LoadConfigValue::class
        ];
    }

    /**
     * @return string
     */
    protected function getRoute()
    {
        return 'oro_product_frontend_product_index';
    }

    /**
     * @return string
     */
    protected function getContentVariantType()
    {
        return 'category_page';
    }
}
