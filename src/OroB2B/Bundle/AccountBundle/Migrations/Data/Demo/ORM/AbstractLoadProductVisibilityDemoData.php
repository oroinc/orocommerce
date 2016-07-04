<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

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
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
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
        return $manager->getRepository('OroB2BProductBundle:Product')->findOneBySku($sku);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return Website
     */
    protected function getWebsite(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => $name]);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return Account
     */
    protected function getAccount(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroB2BAccountBundle:Account')->findOneBy(['name' => $name]);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @return AccountGroup
     */
    protected function getAccountGroup(ObjectManager $manager, $name)
    {
        return $manager->getRepository('OroB2BAccountBundle:AccountGroup')->findOneBy(['name' => $name]);
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
        $productIds = $manager->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('product')
            ->select('product.id')
            ->innerJoin('OroB2BCatalogBundle:Category', 'category', 'WITH', 'product MEMBER OF category.products')
            ->getQuery()
            ->getArrayResult();
        $productIds = array_map('current', $productIds);
        if (!$productIds) {
            return;
        }

        /** @var ProductVisibility[] $visibilities */
        $visibilities = $manager->getRepository('OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility')
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
                'OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility',
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
