<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadCheckoutSubtotals;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;

/**
 * @dbIsolationPerTest
 */
class CheckoutSubtotalRepositoryTest extends FrontendWebTestCase
{
    /** @var CheckoutSubtotalRepository */
    protected $repository;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
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

    public function testInvalidateByCombinedPriceListOnCompletedCheckouts()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_9);

        $cpl = $this->getReference('1f');
        $this->getRepository()->invalidateByCombinedPriceList([$cpl->getId()]);

        $this->assertTrue($checkoutSubtotal->isValid());
    }

    public function testInvalidateByPriceList()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_7);

        $priceList = $this->getReference('price_list_1');
        $this->getRepository()->invalidateByPriceList([$priceList->getId()]);

        $this->assertFalse($checkoutSubtotal->isValid());
    }

    public function testInvalidateByPriceListOnCompletedCheckouts()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_8);

        $priceList = $this->getReference('price_list_1');
        $this->getRepository()->invalidateByPriceList([$priceList->getId()]);

        $this->assertTrue($checkoutSubtotal->isValid());
    }

    public function testInvalidateByCustomers()
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

    public function testInvalidateByCustomersOnCompletedCheckouts()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_8);
        $this->getRepository()->invalidateByCustomers(
            [$checkoutSubtotal->getCheckout()->getCustomer()->getId()],
            $checkoutSubtotal->getCheckout()->getWebsite()->getId()
        );
        $this->getManager()->refresh($checkoutSubtotal);

        $this->assertTrue($checkoutSubtotal->isValid());
    }

    public function testInvalidateByCustomerGroups()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_7);
        $this->preparePriceListRelationForCustomerGroupChecks($checkoutSubtotal);

        $this->getRepository()->invalidateByCustomerGroups(
            [$checkoutSubtotal->getCheckout()->getCustomer()->getGroup()->getId()],
            $checkoutSubtotal->getCheckout()->getWebsite()->getId()
        );
        $this->getManager()->refresh($checkoutSubtotal);

        $this->assertFalse($checkoutSubtotal->isValid());
    }

    public function testInvalidateByCustomerGroupsOnCompletedCheckouts()
    {
        /** @var CheckoutSubtotal $checkoutSubtotal */
        $checkoutSubtotal = $this->getReference(LoadCheckoutSubtotals::CHECKOUT_SUBTOTAL_8);
        $this->preparePriceListRelationForCustomerGroupChecks($checkoutSubtotal);

        $this->getRepository()->invalidateByCustomerGroups(
            [$checkoutSubtotal->getCheckout()->getCustomer()->getGroup()->getId()],
            $checkoutSubtotal->getCheckout()->getWebsite()->getId()
        );
        $this->getManager()->refresh($checkoutSubtotal);

        $this->assertTrue($checkoutSubtotal->isValid());
    }

    /**
     * @return EntityManagerInterface
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

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function preparePriceListRelationForCustomerGroupChecks(CheckoutSubtotal $checkoutSubtotal): void
    {
        $customerGroupId = $checkoutSubtotal->getCheckout()->getCustomer()->getGroup()->getId();
        $websiteId = $checkoutSubtotal->getCheckout()->getWebsite()->getId();
        $priceListId = $this->getReference('price_list_1')->getId();

        $connection = $this->getManager()->getConnection();
        $connection->executeQuery('DELETE FROM oro_price_list_to_customer');
        $connection->executeQuery(sprintf(
            'INSERT INTO oro_price_list_to_cus_group(price_list_id, website_id, customer_group_id, sort_order) 
            VALUES(%d, %d, %d, 0)',
            $priceListId,
            $websiteId,
            $customerGroupId
        ));
    }
}
