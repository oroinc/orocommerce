<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads product categories demo data.
 */
class LoadProductCategoryDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    private array $categories = [];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadCategoryDemoData::class,
            LoadProductDemoData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
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

        $this->categories = [];
    }

    private function getProductBySku(EntityManagerInterface $manager, string $sku): ?Product
    {
        return $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
    }

    private function getCategoryByDefaultTitle(
        EntityManagerInterface $manager,
        string $title,
        Organization $organization
    ): ?Category {
        if (!array_key_exists($title, $this->categories)) {
            $this->categories[$title] = $this->getCategory($manager, $organization, $title);
        }

        return $this->categories[$title];
    }

    private function getCategory(ObjectManager $manager, OrganizationInterface $organization, string $title): ?Category
    {
        $queryBuilder = $manager->getRepository(Category::class)->findOneByDefaultTitleQueryBuilder($title);
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
