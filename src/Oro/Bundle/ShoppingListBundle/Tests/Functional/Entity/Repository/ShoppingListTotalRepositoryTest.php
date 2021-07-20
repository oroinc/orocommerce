<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolationPerTest
 */
class ShoppingListTotalRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $manager;

    /** @var ShoppingListTotalRepository */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadGuestShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );

        $this->manager = $this->getContainer()->get('doctrine')->getManagerForClass(ShoppingListTotal::class);
        $this->repository = $this->manager->getRepository(ShoppingListTotal::class);
    }

    public function testInvalidateByCombinedPriceList()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);

        $invalidTotal = $this->createTotal($shoppingList);

        $cpl = $this->getReference('1f');
        $this->repository->invalidateByCombinedPriceList([$cpl->getId()]);

        $this->manager->refresh($invalidTotal);
        $this->assertFalse($invalidTotal->isValid());
    }

    public function testInvalidateByCustomers()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_3);
        $shoppingListTotal = $this->setTotalValid($shoppingList);

        $this->repository->invalidateByCustomers(
            [$shoppingListTotal->getShoppingList()->getCustomer()->getId()],
            $shoppingListTotal->getShoppingList()->getWebsite()->getId()
        );

        $this->manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
    }

    public function testInvalidateByCustomerGroups()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_7);
        $shoppingListTotal = $this->setTotalValid($shoppingList);
        $this->preparePriceListRelationForCustomerGroupChecks($shoppingListTotal);

        $this->repository->invalidateByCustomerGroupsForFlatPricing(
            [$shoppingListTotal->getShoppingList()->getCustomer()->getGroup()->getId()],
            $shoppingListTotal->getShoppingList()->getWebsite()->getId()
        );

        $this->manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
    }

    public function testInvalidateByWebsites()
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_7);
        $shoppingListTotal = $this->setTotalValid($shoppingList);
        $this->preparePriceListRelationForWebsiteChecks();

        $this->repository->invalidateByWebsitesForFlatPricing(
            [$shoppingListTotal->getShoppingList()->getWebsite()->getId()]
        );

        $this->manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
    }

    public function testInvalidateGuestShoppingLists()
    {
        $shoppingList = $this->getReference(LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1);
        $shoppingListTotal = $this->setTotalValid($shoppingList);

        $this->repository->invalidateGuestShoppingLists($shoppingList->getWebsite()->getId());

        $this->manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
    }

    public function testInvalidateByProducts()
    {
        /** @var Website $website */
        $website = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Website::class)
            ->getDefaultWebsite();

        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product7 = $this->getReference(LoadProductData::PRODUCT_7);

        /** @var ShoppingListTotal $totalSL4 */
        $totalSL4 = $this->repository->findOneBy([
            'shoppingList' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_4)
        ]);
        $this->assertTrue($totalSL4->isValid());
        /** @var ShoppingListTotal $totalSL5 */
        $totalSL5 = $this->repository->findOneBy([
            'shoppingList' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_5)
        ]);
        $this->assertTrue($totalSL5->isValid());
        /** @var ShoppingListTotal $totalSL7 */
        $totalSL7 = $this->repository->findOneBy([
            'shoppingList' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_7)
        ]);
        $this->assertTrue($totalSL7->isValid());

        $this->repository->invalidateByProducts(
            $website,
            [$product1->getId(), $product7->getId()]
        );

        $this->manager->refresh($totalSL4);
        $this->assertTrue($totalSL4->isValid());
        $this->manager->refresh($totalSL5);
        $this->assertFalse($totalSL5->isValid());
        $this->manager->refresh($totalSL7);
        $this->assertFalse($totalSL7->isValid());
    }

    public function testInvalidateByWebsite()
    {
        /** @var Website $website */
        $website = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Website::class)
            ->getDefaultWebsite();

        /** @var ShoppingListTotal $total */
        $total = $this->repository->findOneBy([
            'shoppingList' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_4)
        ]);
        $this->assertTrue($total->isValid());

        $this->repository->invalidateByWebsite($website);

        $this->manager->refresh($total);
        $this->assertFalse($total->isValid());
    }

    /**
     * @param ShoppingList $shoppingList
     * @return ShoppingListTotal
     */
    private function setTotalValid(ShoppingList $shoppingList)
    {
        $shoppingListTotal = $this->repository->findOneBy(['shoppingList' => $shoppingList]);
        if (!$shoppingListTotal) {
            $shoppingListTotal = $this->createTotal($shoppingList);
        } else {
            $shoppingListTotal->setValid(true);

            $this->manager->flush();
        }

        return $shoppingListTotal;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return ShoppingListTotal
     */
    private function createTotal(ShoppingList $shoppingList)
    {
        $currency = 'USD';
        $subtotal = (new Subtotal())->setCurrency($currency)->setAmount(1);
        /** @var ShoppingListTotalRepository $repo */
        $repo = $this->getContainer()->get('doctrine')->getRepository(ShoppingListTotal::class);
        $total = $repo->findOneBy(['shoppingList' => $shoppingList, 'currency' => $currency]);

        if (!$total) {
            $total = new ShoppingListTotal($shoppingList, $currency);
        }
        $total->setValid(true);
        $total->setSubtotal($subtotal);

        $this->manager->persist($total);
        $this->manager->flush();

        return $total;
    }

    private function preparePriceListRelationForCustomerGroupChecks(ShoppingListTotal $shoppingListTotal)
    {
        $shoppingList = $shoppingListTotal->getShoppingList();
        $customerGroupId = $shoppingList->getCustomer()->getGroup()->getId();
        $websiteId = $shoppingList->getWebsite()->getId();
        $priceListId = $this->getReference('price_list_1')->getId();

        $connection = $this->manager->getConnection();
        $connection->executeQuery('DELETE FROM oro_price_list_to_customer');
        $connection->executeQuery(sprintf(
            'INSERT INTO oro_price_list_to_cus_group(price_list_id, website_id, customer_group_id, sort_order) 
            VALUES(%d, %d, %d, 0)',
            $priceListId,
            $websiteId,
            $customerGroupId
        ));
    }

    private function preparePriceListRelationForWebsiteChecks()
    {
        $connection = $this->manager->getConnection();
        $connection->executeQuery('DELETE FROM oro_price_list_to_customer');
        $connection->executeQuery('DELETE FROM oro_price_list_to_cus_group');
    }
}
