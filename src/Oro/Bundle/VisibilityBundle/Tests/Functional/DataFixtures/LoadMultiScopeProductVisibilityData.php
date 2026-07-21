<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads explicit product visibility in a single website scope for multi-scope tests.
 */
class LoadMultiScopeProductVisibilityData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    public const PRODUCT_WITH_STATIC_VISIBILITY = LoadProductData::PRODUCT_1;
    public const WEBSITE_WITH_STATIC_VISIBILITY = LoadWebsiteData::WEBSITE1;
    public const REFERENCE_STATIC_VISIBILITY = 'multi_scope_product_visibility_static';

    private ContainerInterface $container;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadWebsiteData::class,
            LoadCategoryProductData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $visibilityScopeProvider = $this->container->get('oro_visibility.provider.visibility_scope_provider');
        /** @var Website $website */
        $website = $this->getReference(self::WEBSITE_WITH_STATIC_VISIBILITY);
        /** @var Product $product */
        $product = $this->getReference(self::PRODUCT_WITH_STATIC_VISIBILITY);

        $scope = $visibilityScopeProvider->getProductVisibilityScope($website);

        $productVisibility = new ProductVisibility();
        $productVisibility->setProduct($product)
            ->setScope($scope)
            ->setVisibility(ProductVisibility::VISIBLE);

        $manager->persist($productVisibility);
        $manager->flush();

        $this->addReference(self::REFERENCE_STATIC_VISIBILITY, $productVisibility);
    }
}
