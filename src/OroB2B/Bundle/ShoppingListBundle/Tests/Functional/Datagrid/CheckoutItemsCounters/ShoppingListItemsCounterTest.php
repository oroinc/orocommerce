<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Datagrid\CheckoutItemsCounters;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\ShoppingListBundle\Datagrid\CheckoutItemsCounters\ShoppingListItemsCounter;

/**
 * @dbIsolation
 */
class ShoppingListItemsCounterTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function testCountsItems()
    {
        /* @var $em EntityManager */
        $em = static::getContainer()->get('doctrine')->getManager();

        $counter = new ShoppingListItemsCounter();

        $checkouts = $em->createQueryBuilder()
            ->select('c.id')
            ->from('OroB2BCheckoutBundle:Checkout', 'c')
            ->getQuery()
            ->getScalarResult();

        $ids = [];

        foreach ($checkouts as $checkout) {
            $ids[] = $checkout['id'];
        }

        $result = $counter->countItems($em, $ids);

        $this->assertTrue(count($result) > 0);

        foreach ($result as $id => $count) {
            $this->assertGreaterThan(0, $id);
            $this->assertGreaterThan(0, $count);
        }
    }
}
