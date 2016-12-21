<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * @dbIsolation
 */
class CheckoutRepositoryTest extends WebTestCase
{
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
                LoadAccountUserData::class,
            ]
        );
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
        $accountUser = $this->getReference(LoadAccountUserData::EMAIL);

        $this->assertSame(
            $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1),
            $this->getRepository()->getCheckoutByQuote($quote, $accountUser)
        );
    }

    public function testFindCheckoutByAccountUserAndSourceCriteria()
    {
        $accountUser = $this->getReference(LoadAccountUserData::EMAIL);
        $criteria = ['quoteDemand' => $this->getReference(LoadQuoteProductDemandData::QUOTE_DEMAND_1)];

        $this->assertSame(
            $this->getReference(LoadQuoteCheckoutsData::CHECKOUT_1),
            $this->getRepository()->findCheckoutByAccountUserAndSourceCriteria($accountUser, $criteria)
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
