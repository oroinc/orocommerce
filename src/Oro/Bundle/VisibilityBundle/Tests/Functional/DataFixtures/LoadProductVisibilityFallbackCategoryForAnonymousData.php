<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductVisibilityFallbackCategoryForAnonymousData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    protected $products = [
        LoadProductData::PRODUCT_1,
        LoadProductData::PRODUCT_2,
        LoadProductData::PRODUCT_3,
        LoadProductData::PRODUCT_4,
        LoadProductData::PRODUCT_5,
        LoadProductData::PRODUCT_6,
        LoadProductData::PRODUCT_7,
        LoadProductData::PRODUCT_8,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryVisibilityData::class,
            LoadCategoryProductData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $defaultWebsite = $this->getDefaultWebsite();

        foreach ($this->products as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);

            /** @var CustomerGroup $anonymousCustomerGroup */
            $anonymousCustomerGroup = $this->getReference(LoadGroups::ANONYMOUS_GROUP);
            $visibility = $this->getCustomerGroupProductVisibility($anonymousCustomerGroup, $product, $defaultWebsite);

            $manager->persist($visibility);
        }

        $manager->flush();
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Product $product
     * @param WebsiteInterface $defaultWebsite
     * @return CustomerGroupProductVisibility
     */
    private function getCustomerGroupProductVisibility(
        CustomerGroup $customerGroup,
        Product $product,
        WebsiteInterface $defaultWebsite
    ) {
        $scope = $this->container->get('oro_visibility.provider.visibility_scope_provider')
            ->getCustomerGroupProductVisibilityScope($customerGroup, $defaultWebsite);
        $customerGroupVisibility = (new CustomerGroupProductVisibility())
            ->setProduct($product)
            ->setScope($scope)
            ->setVisibility(CustomerGroupProductVisibility::CATEGORY);

        return $customerGroupVisibility;
    }

    /**
     * @return WebsiteInterface
     */
    private function getDefaultWebsite()
    {
        return $this->container
            ->get('oro_website.manager')
            ->getDefaultWebsite();
    }
}
