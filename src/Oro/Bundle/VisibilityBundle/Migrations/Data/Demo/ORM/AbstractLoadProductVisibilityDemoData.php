<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\VisibilityBundle\Entity\Account;
use Oro\Bundle\VisibilityBundle\Entity\AccountGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

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
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            __NAMESPACE__.'\LoadAccountDemoData',
            __NAMESPACE__.'\LoadCategoryVisibilityDemoData',
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
     * @param string $name
     * @return Website
     */
    protected function getWebsite(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroWebsiteBundle:Website')->findOneBy(['name' => $name]);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return Account
     */
    protected function getAccount(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroAccountBundle:Account')->findOneBy(['name' => $name]);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return AccountGroup
     */
    protected function getAccountGroup(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroAccountBundle:AccountGroup')->findOneBy(['name' => $name]);
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
     * @param Website $website
     * @param VisibilityInterface|WebsiteAwareInterface $visibility
     * @param Product $product
     * @param string $visibilityValue
     */
    protected function saveVisibility(
        ObjectManager $manager,
        Website $website,
        VisibilityInterface $visibility,
        Product $product,
        $visibilityValue
    ) {
        $visibility->setWebsite($website);
        $visibility->setTargetEntity($product)->setVisibility($visibilityValue);
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
     * @param Website $website
     * @param Product $product
     * @param string $visibility
     */
    protected function setProductVisibility(ObjectManager $manager, $row, $website, $product, $visibility)
    {
        if ($row['all']) {
            $productVisibility = $this->findVisibilityEntity(
                $manager,
                'Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility',
                ['website' => $website, 'product' => $product]
            );
            if (!$productVisibility) {
                $productVisibility = new ProductVisibility();
            }
            $this->saveVisibility($manager, $website, $productVisibility, $product, $visibility);
        }

        if ($row['account']) {
            $accountVisibility = new AccountProductVisibility();
            $accountVisibility->setAccount($this->getAccount($manager, $row['account']));
            $this->saveVisibility($manager, $website, $accountVisibility, $product, $visibility);
        }

        if ($row['accountGroup']) {
            $accountGroupVisibility = new AccountGroupProductVisibility();
            $accountGroupVisibility->setAccountGroup($this->getAccountGroup($manager, $row['accountGroup']));
            $this->saveVisibility($manager, $website, $accountGroupVisibility, $product, $visibility);
        }
    }
}
