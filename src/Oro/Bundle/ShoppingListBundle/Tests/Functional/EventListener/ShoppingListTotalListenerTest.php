<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadGuestShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ShoppingListTotalListenerTest extends WebTestCase
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
                LoadGuestShoppingListLineItems::class,
                LoadCombinedProductPrices::class,
            ]
        );

        $this->manager = $this->getContainer()->get('doctrine')->getManagerForClass(ShoppingListTotal::class);
        $this->repository = $this->manager->getRepository(ShoppingListTotal::class);
    }

    public function testOnCustomerGroupPriceListUpdate()
    {
        $shoppingList = $this->getReference(LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1);
        $shoppingListTotal = $this->createTotal($shoppingList);
        $website = $shoppingList->getWebsite();

        $groupId = $this->getContainer()->get('oro_config.global')->get('oro_customer.anonymous_customer_group');

        $event = new CustomerGroupCPLUpdateEvent([
            ['websiteId' => $website->getId(), 'customerGroups' => [100, 200, 300, $groupId]]
        ]);

        $this->getContainer()
            ->get('event_dispatcher')
            ->dispatch('oro_pricing.customer_group.combined_price_list.update', $event);

        $this->manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
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
