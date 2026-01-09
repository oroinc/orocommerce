<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadScopeCustomerGroupDemoData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductKitDemoData;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The base class for fixtures that load product visibilities demo data.
 */
abstract class AbstractLoadProductVisibilityDemoData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadProductDemoData::class,
            LoadProductKitDemoData::class,
            LoadScopeCustomerGroupDemoData::class,
            LoadCategoryVisibilityDemoData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->resetVisibilities($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate($this->getDataFile());
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $this->setProductVisibility($manager, $row);
        }

        fclose($handler);
    }

    /**
     * Set fallback to parent category for all products with categories
     */
    protected function resetVisibilities(ObjectManager $manager): void
    {
        // products with categories
        $productIds = $manager->getRepository(Product::class)
            ->createQueryBuilder('product')
            ->select('product.id')
            ->innerJoin(Category::class, 'category', 'WITH', 'product MEMBER OF category.products')
            ->getQuery()
            ->getArrayResult();
        $productIds = array_map('current', $productIds);
        if (!$productIds) {
            return;
        }

        /** @var ProductVisibility[] $visibilities */
        $visibilities = $manager->getRepository(ProductVisibility::class)
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

    protected function setProductVisibility(ObjectManager $manager, array $row): void
    {
        $scopeProvider = $this->container->get('oro_visibility.provider.visibility_scope_provider');
        $product = $manager->getRepository(Product::class)->findOneBy(['sku' => $row['product']]);
        $website = $this->getWebsite($manager, $row);

        if ($row['all']) {
            $visibility = $manager->getRepository(ProductVisibility::class)->findOneBy(['product' => $product]);
            if (!$visibility) {
                $visibility = new ProductVisibility();
            }
            $visibility->setScope($scopeProvider->getProductVisibilityScope($website));
        } elseif ($row['customer']) {
            $visibility = new CustomerProductVisibility();
            $customer = $manager->getRepository(Customer::class)->findOneByName($row['customer']);
            $scope = $scopeProvider->getCustomerProductVisibilityScope($customer, $website);
            $visibility->setScope($scope);
        } elseif ($row['customerGroup']) {
            $visibility = new CustomerGroupProductVisibility();
            $customerGroup = $manager->getRepository(CustomerGroup::class)->findOneByName($row['customerGroup']);
            $scope = $scopeProvider->getCustomerGroupProductVisibilityScope($customerGroup, $website);
            $visibility->setScope($scope);
        } else {
            throw new \RuntimeException('Visibility type undefined');
        }

        $visibility->setVisibility($row['visibility'])
            ->setProduct($product);

        $manager->persist($visibility);
        $manager->flush();
    }

    abstract protected function getWebsite(ObjectManager $manager, array $row): Website;

    abstract protected function getDataFile(): string;
}
