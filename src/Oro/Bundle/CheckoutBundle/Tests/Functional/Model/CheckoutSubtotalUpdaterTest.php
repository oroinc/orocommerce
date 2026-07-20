<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Model;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class CheckoutSubtotalUpdaterTest extends WebTestCase
{
    private CheckoutSubtotalUpdater $updater;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadShoppingListsCheckoutsData::class]);
        $this->updater = self::getContainer()->get('oro_checkout.model.checkout_subtotal_updater');
    }

    public function testRecalculateInvalidSubtotalsWithMoreThanBatchCount(): void
    {
        $checkoutRefs = [
            LoadShoppingListsCheckoutsData::CHECKOUT_1,
            LoadShoppingListsCheckoutsData::CHECKOUT_2,
            LoadShoppingListsCheckoutsData::CHECKOUT_3,
            LoadShoppingListsCheckoutsData::CHECKOUT_4,
            LoadShoppingListsCheckoutsData::CHECKOUT_7,
            LoadShoppingListsCheckoutsData::CHECKOUT_8,
        ];

        $em = self::getContainer()->get('doctrine')->getManagerForClass(CheckoutSubtotal::class);
        foreach ($checkoutRefs as $ref) {
            /** @var Checkout $checkout */
            $checkout = $this->getReference($ref);
            $subtotal = new CheckoutSubtotal($checkout, 'USD');
            $subtotal->setValid(false);
            $em->persist($subtotal);
        }
        $em->flush();

        $this->updater->setBatchSize(5);
        $this->updater->recalculateInvalidSubtotals();

        self::assertSame(
            0,
            $em->getRepository(CheckoutSubtotal::class)->count(['valid' => false])
        );
    }
}
