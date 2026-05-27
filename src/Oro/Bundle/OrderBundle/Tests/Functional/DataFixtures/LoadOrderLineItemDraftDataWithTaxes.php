<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;

/**
 * Loads order line item draft data with proper tax configuration for testing.
 * Sets up ORDER_1 with customer and addresses for tax calculation.
 */
class LoadOrderLineItemDraftDataWithTaxes extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrderLineItemDraftData::class,
            LoadTaxRules::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        /** @var Order $order1 */
        $order1 = $this->getReference(LoadOrders::ORDER_1);
        // Add a customer with a tax code specified.
        $order1->setCustomer($customer);
        $order1->setCustomerUser(null);

        /** @var Order $order1Draft */
        $order1Draft = $this->getReference(LoadOrders::ORDER_1 . '_DRAFT');
        // Add a customer with a tax code specified.
        $order1Draft->setCustomer($customer);
        $order1Draft->setCustomerUser(null);

        // Add billing and shipping addresses with US/NY location for tax calculation
        $billingAddress = new OrderAddress();
        $billingAddress->setCountry(
            $manager->getRepository(Country::class)->find(LoadTaxJurisdictions::COUNTRY_US)
        );
        $billingAddress->setRegion(
            $manager->getRepository(Region::class)->find(LoadTaxJurisdictions::STATE_US_NY)
        );
        $billingAddress->setPostalCode('10001');
        $billingAddress->setStreet('123 Main St');
        $billingAddress->setCity('New York');

        $shippingAddress = clone $billingAddress;

        $order1->setBillingAddress($billingAddress);
        $order1->setShippingAddress($shippingAddress);

        $manager->flush();
    }
}
