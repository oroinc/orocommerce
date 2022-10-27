<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
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
    use ConfigManagerAwareTestTrait;

    /** @var EntityManager */
    protected $manager;

    /** @var ShoppingListTotalRepository */
    protected $repository;

    protected function setUp(): void
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

        $groupId = self::getConfigManager('global')->get('oro_customer.anonymous_customer_group');

        $event = new CustomerGroupCPLUpdateEvent([
            ['websiteId' => $website->getId(), 'customerGroups' => [100, 200, 300, $groupId]]
        ]);

        $this->getContainer()
            ->get('event_dispatcher')
            ->dispatch($event, 'oro_pricing.customer_group.combined_price_list.update');

        $this->manager->refresh($shoppingListTotal);
        $this->assertFalse($shoppingListTotal->isValid());
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
}
