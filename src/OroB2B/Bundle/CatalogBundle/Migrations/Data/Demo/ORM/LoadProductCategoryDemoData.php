<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

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

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            $category = $this->getCategoryByDefaultTitle($manager, $row['category']);
            if ($category) {
                $category->addProduct($product);
                $manager->persist($category);
            }
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param string        $sku
     *
     * @return Product|null
     */
    protected function getProductBySku(EntityManager $manager, $sku)
    {
        return $this->getProductRepository($manager)->findOneBy(['sku' => $sku]);
    }

    /**
     * @param EntityManager $manager
     * @param string        $title
     *
     * @return Category|null
     */
    protected function getCategoryByDefaultTitle(EntityManager $manager, $title)
    {
        if (!array_key_exists($title, $this->categories)) {
            $this->categories[$title] = $this->getCategoryRepository($manager)->findOneByDefaultTitle($title);
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
}
