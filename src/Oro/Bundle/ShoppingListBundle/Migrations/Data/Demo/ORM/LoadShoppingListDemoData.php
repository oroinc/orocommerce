<?php
declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerUserDemoData;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\Demo\ORM\LoadFixedProductIntegration;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;
use Oro\Bundle\PromotionBundle\Migrations\Data\Demo\ORM\LoadPromotionDemoData;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loading shopping list demo data.
 */
class LoadShoppingListDemoData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    private ObjectManager $manager;
    private CategoryRepository $categoryRepository;
    private ProductRepository $productRepository;
    private Organization $organization;
    private Website $website;

    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
            LoadCustomerUserDemoData::class,
            LoadWebsiteData::class,
            LoadProductDemoData::class,
            LoadProductUnitPrecisionDemoData::class
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->categoryRepository = $manager->getRepository(Category::class);
        $this->productRepository = $manager->getRepository(Product::class);
        $this->organization = $this->getFirstUser($manager)->getOrganization();
        $this->website = $manager->getRepository(Website::class)
            ->findOneBy(['name' => LoadWebsiteData::DEFAULT_WEBSITE_NAME]);

        $customerUser = $manager->getRepository(CustomerUser::class)->findOneBy([]);

        /** This list includes some products (new arrivals) that are eligible for the
         * "Buy 10 Get 5 with $2 off on new arrivals in Medical Footwear" promotion
         * @see LoadPromotionDemoData */
        $medicalList = $this->createShoppingList($customerUser, 'Care Team Reorder');
        $footwear = $this->getProductsInCategory('Footwear');
        foreach ($footwear as $product) {
            if (!\in_array($product->getSku(), LoadProductDemoData::OUT_OF_STOCK_SKUS)) {
                $this->addLineItem($medicalList, $product, 15);
            }
        }

        /** This list will get a free shipping with Fixed Product Shipping method during checkout
         *  @see LoadPromotionDemoData */
        $officeList = $this->createShoppingList($customerUser, 'Office Updates');
        $furniture = $this->getProductsInCategory('Office Furniture');
        foreach ($furniture as $product) {
            if (!\in_array($product->getSku(), LoadProductDemoData::OUT_OF_STOCK_SKUS)) {
                $this->addLineItem($officeList, $product, 1);
            }
        }

        /** Picking random products, but from those that are eligible for the
         * free shipping promotion with Flat Rate shipping method
         * @see LoadPromotionDemoData */
        $defaultList = $this->createShoppingList($customerUser, 'Shopping List', true);
        $defaultList->setNotes('Do not forget to use SALE25 coupon when checking out this order!');
        $randomProducts = $this->productRepository->matching(
            Criteria::create()
                ->andWhere(Criteria::expr()->gt('id', (string)LoadFixedProductIntegration::PRODUCT_ID_THRESHOLD))
                ->setMaxResults(5)
        );

        foreach ($randomProducts as $product) {
            if (!\in_array($product->getSku(), LoadProductDemoData::OUT_OF_STOCK_SKUS)) {
                $this->addLineItem($defaultList, $product);
            }
        }

        $manager->flush();
    }

    /**
     * @return Product[]
     */
    private function getProductsInCategory(string $categoryTitle): array
    {
        $category = $this->categoryRepository->findOneOrNullByDefaultTitleAndParent(
            $categoryTitle,
            $this->organization
        );

        return $this->productRepository->findBy(['category' => $category]);
    }

    private function addLineItem(ShoppingList $shoppingList, Product $product, ?int $qty = null)
    {
        $lineItem = (new LineItem())
            ->setShoppingList($shoppingList)
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOwner($shoppingList->getOwner())
            ->setOrganization($shoppingList->getOrganization())
            ->setProduct($product)
            ->setQuantity($qty ?? mt_rand(10, 100))
            ->setUnit($product->getUnitPrecisions()->current()->getUnit());

        $shoppingList->addLineItem($lineItem);

        $this->manager->persist($lineItem);
    }

    private function createShoppingList(CustomerUser $customerUser, string $label, bool $current = false): ShoppingList
    {
        $shoppingList = (new ShoppingList())
            ->setOrganization($customerUser->getOrganization())
            ->setOwner($customerUser->getOwner())
            ->setCustomerUser($customerUser)
            ->setCustomer($customerUser->getCustomer())
            ->setCurrent($current)
            ->setLabel($label)
            ->setWebsite($this->website)
        ;

        $this->manager->persist($shoppingList);

        return $shoppingList;
    }
}
