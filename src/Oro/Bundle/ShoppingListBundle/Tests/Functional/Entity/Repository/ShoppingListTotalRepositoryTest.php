<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShoppingListTotalRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $manager;

    /** @var ShoppingListTotalRepository */
    protected $repository;

    protected function setUp()
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

    public function testInvalidateGuestShoppingLists()
    {
        $shoppingList = $this->getReference(LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1);
        $shoppingListTotal = $this->setTotalValid($shoppingList);

        $this->repository->invalidateGuestShoppingLists($shoppingList->getWebsite()->getId());

        $this->manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
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
        $subtotal = (new Subtotal())->setCurrency('USD')->setAmount(1);

        $total = new ShoppingListTotal($shoppingList, 'USD');
        $total->setValid(true);
        $total->setSubtotal($subtotal);

        $this->manager->persist($total);
        $this->manager->flush();

        return $total;
    }
}
