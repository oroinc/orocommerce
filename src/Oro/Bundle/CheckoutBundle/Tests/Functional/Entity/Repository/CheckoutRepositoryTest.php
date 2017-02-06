<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * @dbIsolation
 */
class CheckoutRepositoryTest extends WebTestCase
{
    /**
     * @var CheckoutRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadQuoteCheckoutsData::class,
                LoadShoppingListsCheckoutsData::class,
                LoadCustomerUserData::class,
            ]
        );

        $this->repository = $this->getRepository();
    }

    /**
     * @return CheckoutRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroCheckoutBundle:Checkout');
    }

    public function testCountItemsPerCheckout()
    {
        $repository = $this->getRepository();

        $checkouts = $repository->findAll();

        $ids = [];

        foreach ($checkouts as $checkout) {
            $ids[] = $checkout->getId();
        }

        $counts = $repository->countItemsPerCheckout($ids);

        $this->assertTrue(count($counts) > 0);

        $found = 0;

        foreach ($checkouts as $checkout) {
            if (isset($counts[$checkout->getId()])) {
                $found++;
            }
        }

        $this->assertEquals(count($ids), $found);
    }

    public function testGetSourcePerCheckout()
    {
        $repository = $this->getRepository();

        $checkouts = $repository->findAll();

        $ids = [];

        $withSource = 0;

        foreach ($checkouts as $checkout) {
            $ids[] = $checkout->getId();

            if (is_object($checkout->getSourceEntity())) {
                $withSource++;
            }
        }

        $sources = $repository->getSourcePerCheckout($ids);

        $found = 0;

        foreach ($checkouts as $checkout) {
            if (isset($sources[$checkout->getId()]) && is_object($sources[$checkout->getId()])) {
                $found++;
            }
        }

        $this->assertEquals($withSource, $found);
    }

    public function testGetCheckoutByQuote()
    {
        $quote = $this->getReference(LoadQuoteData::QUOTE1);
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        $this->assertSame(
            $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1),
            $this->getRepository()->getCheckoutByQuote($quote, $customerUser)
        );
    }

    public function testFindCheckoutByCustomerUserAndSourceCriteriaByQuoteDemand()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        $criteria = ['quoteDemand' => $this->getReference(LoadQuoteProductDemandData::QUOTE_DEMAND_1)];

        $this->assertSame(
            $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1),
            $this->getRepository()->findCheckoutByCustomerUserAndSourceCriteria($customerUser, $criteria)
        );
    }

    public function testFindCheckoutByCustomerUserAndSourceCriteriaByShoppingList()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);
        $criteria = ['shoppingList' => $this->getReference(LoadShoppingLists::SHOPPING_LIST_7)];

        $this->assertSame(
            $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_7),
            $this->getRepository()->findCheckoutByCustomerUserAndSourceCriteria($customerUser, $criteria)
        );
    }

    public function testDeleteWithoutWorkflowItem()
    {
        $repository = $this->getRepository();

        $checkouts = $repository->findBy(['deleted' => false]);

        $this->deleteAllWorkflowItems();
        $repository->deleteWithoutWorkflowItem();

        $this->assertCount(count($checkouts), $repository->findBy(['deleted' => true]));
    }

    public function testFindByType()
    {
        $checkouts = $this->repository->findByPaymentMethod(LoadQuoteCheckoutsData::PAYMENT_METHOD);

        static::assertContains($this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1), $checkouts);
        static::assertContains($this->getReference(LoadQuoteCheckoutsData::CHECKOUT_2), $checkouts);
    }

    protected function deleteAllWorkflowItems()
    {
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(WorkflowItem::class);
        $repository = $manager->getRepository(WorkflowItem::class);

        $workflowItems = $repository->findAll();

        foreach ($workflowItems as $workflowItem) {
            $manager->remove($workflowItem);
        }

        $manager->flush();
    }
}
