<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutSubtotals;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;

class CheckoutSubtotalRepositoryTest extends FrontendWebTestCase
{
    /** @var CheckoutSubtotalRepository */
    protected $repository;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->setCurrentWebsite('default');
        $this->loadFixtures(
            [
                LoadCombinedProductPrices::class,
                LoadCheckoutSubtotals::class
            ]
        );

        $this->repository = $this->getRepository();
    }

    public function testInvalidateByCombinedPriceList()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_3);

        $cpl = $this->getReference('1f');
        $this->getRepository()->invalidateByCombinedPriceList([$cpl->getId()]);

        $this->assertFalse($checkoutSubtotal->isValid());
    }

    public function testInvalidateByCustomerUsers()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_7);
        $this->getRepository()->invalidateByCustomers(
            [$checkoutSubtotal->getCheckout()->getCustomer()->getId()],
            $checkoutSubtotal->getCheckout()->getWebsite()->getId()
        );
        $this->getManager()->refresh($checkoutSubtotal);

        $this->assertFalse($checkoutSubtotal->isValid());
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(CheckoutSubtotal::class);
    }

    /**
     * @return CheckoutSubtotalRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository(CheckoutSubtotal::class);
    }
}
