<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\BaseCheckoutRepository;

/**
 * @dbIsolation
 */
class BaseCheckoutRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadQuoteCheckoutsData',
                'OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData',
            ]
        );
    }

    /**
     * @return BaseCheckoutRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BCheckoutBundle:BaseCheckout');
    }

    public function testCountItemsByIds()
    {
        $repository = $this->getRepository();

        $checkouts = $repository->findAll();

        $ids = [];

        foreach ($checkouts as $checkout) {
            $ids[] = $checkout->getId();
        }

        $counts = $repository->countItemsByIds($ids);

        $this->assertTrue(count($counts) > 0);

        $found = 0;

        foreach ($checkouts as $checkout) {
            if (isset($counts[$checkout->getId()])) {
                $found++;
            }
        }

        $this->assertEquals(count($ids), $found);
    }

    public function testGetSourcesByIds()
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

        $sources = $repository->getSourcesByIds($ids);

        $found = 0;

        foreach ($checkouts as $checkout) {
            if (isset($sources[$checkout->getId()]) && is_object($sources[$checkout->getId()])) {
                $found++;
            }
        }

        $this->assertEquals($withSource, $found);
    }
}
