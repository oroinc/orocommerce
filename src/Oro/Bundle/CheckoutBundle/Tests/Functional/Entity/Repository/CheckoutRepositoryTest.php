<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;

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
                'Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData',
                'Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData',
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
}
