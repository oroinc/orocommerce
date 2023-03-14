<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOrganizationProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SubOrderOrganizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SubOrderOrganizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new SubOrderOrganizationProvider();
    }

    public function testGetOrganizationWhenNoLineItems(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order organization.');

        $this->provider->getOrganization(new ArrayCollection([]), 'product.id:1');
    }

    public function testGetOrganizationWhenCheckoutDoesNotHaveOrganization(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to determine order organization.');

        $checkout = new Checkout();
        $lineItem = new CheckoutLineItem();
        $lineItem->setCheckout($checkout);
        $lineItems = new ArrayCollection([$lineItem]);

        $this->provider->getOrganization($lineItems, 'product.id:1');
    }

    public function testGetOrganizationWhenCheckoutHasOrganization(): void
    {
        $organization = $this->createMock(Organization::class);
        $checkout = new Checkout();
        $checkout->setOrganization($organization);
        $lineItem = new CheckoutLineItem();
        $lineItem->setCheckout($checkout);
        $lineItems = new ArrayCollection([$lineItem]);

        self::assertSame(
            $organization,
            $this->provider->getOrganization($lineItems, 'product.id:1')
        );
    }
}
