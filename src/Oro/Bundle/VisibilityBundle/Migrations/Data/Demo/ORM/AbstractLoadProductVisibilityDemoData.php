<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadScopeCustomerGroupDemoData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractLoadProductVisibilityDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;
    
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
    public function getDependencies()
    {
        return [
            LoadProductDemoData::class,
            LoadScopeCustomerGroupDemoData::class,
            LoadCategoryVisibilityDemoData::class,
        ];
    }

    /**
     * @param ObjectManager $manager
     * @param string $sku
     * @return Product
     */
    protected function getProduct(ObjectManager $manager, $sku)
    {
        return $manager->getRepository('OroProductBundle:Product')->findOneBySku($sku);
    }

    /**
     * @param ObjectManager $manager
     * @param int $id
     * @return Scope
     */
    protected function getScopeCustomer(ObjectManager $manager, $id)
    {
        return $manager->getRepository('OroScopeBundle:Scope')->findOneBy(['id' => $id]);
    }

    /**
     * @param ObjectManager $manager
     * @param int $id
     * @return Scope
     */
    protected function getCustomerGroup(ObjectManager $manager, $id)
    {
        return $manager->getRepository('OroScopeBundle:Scope')->findOneBy(['id' => $id]);
    }

    /**
     * @param ObjectManager $manager
     * @param string $class
     * @param array $criteria
     * @return object
     */
    protected function findVisibilityEntity(ObjectManager $manager, $class, array $criteria)
    {
        return $manager->getRepository($class)->findOneBy($criteria);
    }

    /**
     * @param ObjectManager $manager
     * @param VisibilityInterface $visibility
     * @param Product $product
     * @param string $visibilityValue
     */
    protected function saveVisibility(
        ObjectManager $manager,
        VisibilityInterface $visibility,
        Product $product,
        $visibilityValue
    ) {
        $visibility->setTargetEntity($product)->setVisibility($visibilityValue);
        $scopeManager = $this->container->get('oro_scope.manager.scope_manager');
        $visibility->setScope($scopeManager->findOrCreate('visibility', $visibility));

        $manager->persist($visibility);
    }

    /**
     * Set fallback to parent category for all products with categories
     *
     * @param ObjectManager $manager
     */
    protected function resetVisibilities(ObjectManager $manager)
    {
        // products with categories
        $productIds = $manager->getRepository('OroProductBundle:Product')
            ->createQueryBuilder('product')
            ->select('product.id')
            ->innerJoin('OroCatalogBundle:Category', 'category', 'WITH', 'product MEMBER OF category.products')
            ->getQuery()
            ->getArrayResult();
        $productIds = array_map('current', $productIds);
        if (!$productIds) {
            return;
        }

        /** @var ProductVisibility[] $visibilities */
        $visibilities = $manager->getRepository('Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility')
            ->createQueryBuilder('visibility')
            ->andWhere('IDENTITY(visibility.product) IN (:productIds)')
            ->setParameter('productIds', $productIds)
            ->getQuery()
            ->getResult();
        foreach ($visibilities as $visibility) {
            $visibility->setVisibility(ProductVisibility::CATEGORY);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param array $row
     * @param Product $product
     * @param string $visibility
     */
    protected function setProductVisibility(ObjectManager $manager, $row, $product, $visibility)
    {
        if ($row['all']) {
            $productVisibility = $this->findVisibilityEntity(
                $manager,
                'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility',
                ['product' => $product]
            );
            if (!$productVisibility) {
                $productVisibility = new ProductVisibility();
            }
            $this->saveVisibility($manager, $productVisibility, $product, $visibility);
        }

        if ($row['scopeCustomer']) {
            $customerVisibility = new CustomerProductVisibility();
            $customerVisibility->setScope($this->getScopeCustomer($manager, $row['scopeCustomer']));
            $this->saveVisibility($manager, $customerVisibility, $product, $visibility);
        }

        if ($row['scopeCustomerGroup']) {
            $customerGroupVisibility = new CustomerGroupProductVisibility();
            $customerGroupVisibility->setScope($this->getScopeCustomerGroup($manager, $row['scopeCustomerGroup']));
            $this->saveVisibility($manager, $customerGroupVisibility, $product, $visibility);
        }
    }
}
