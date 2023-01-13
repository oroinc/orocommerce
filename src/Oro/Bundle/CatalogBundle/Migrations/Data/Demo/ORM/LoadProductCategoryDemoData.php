<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads product categories demo data
 */
class LoadProductCategoryDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $products = [];

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @var EntityRepository
     */
    protected $productRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData',
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $defaultOrganization = $manager->getRepository(Organization::class)->getFirst();

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            if (!empty($row['category_sort_order'])) {
                $product->setCategorySortOrder($row['category_sort_order']);
            }
            $category = $this->getCategoryByDefaultTitle($manager, $row['category'], $defaultOrganization);
            if ($category) {
                $category->addProduct($product);
                $manager->persist($category);
            }
        }

        fclose($handler);

        $manager->flush();
    }

    protected function getProductBySku(EntityManagerInterface $manager, $sku): ?Product
    {
        return $this->getProductRepository($manager)->findOneBy(['sku' => $sku]);
    }

    protected function getCategoryByDefaultTitle(
        EntityManagerInterface $manager,
        string $title,
        Organization $organization
    ): ?Category {
        if (!array_key_exists($title, $this->categories)) {
            $this->categories[$title] = $this->getCategory($manager, $organization, $title);
        }

        return $this->categories[$title];
    }

    /**
     * @param ObjectManager $manager
     *
     * @return EntityRepository
     */
    protected function getProductRepository(ObjectManager $manager)
    {
        if (!$this->productRepository) {
            $this->productRepository = $manager->getRepository('OroProductBundle:Product');
        }

        return $this->productRepository;
    }

    /**
     * @param ObjectManager $manager
     *
     * @return CategoryRepository
     */
    protected function getCategoryRepository(ObjectManager $manager)
    {
        if (!$this->categoryRepository) {
            $this->categoryRepository = $manager->getRepository('OroCatalogBundle:Category');
        }

        return $this->categoryRepository;
    }

    private function getCategory(ObjectManager $manager, OrganizationInterface $organization, string $title): ?Category
    {
        $queryBuilder = $this->getCategoryRepository($manager)->findOneByDefaultTitleQueryBuilder($title);
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
