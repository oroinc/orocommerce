<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Form\Type\CheckoutShipUntilType;
use Oro\Bundle\InventoryBundle\Form\Extension\CheckoutShipUntilFormExtension;
use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutShipUntilFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductUpcomingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $provider;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutLineItemsManager;

    /**
     * @var DateTimeFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateTimeFormatter;

    /**
     * @var CheckoutShipUntilFormExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->provider = $this->createMock(ProductUpcomingProvider::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->dateTimeFormatter = $this->createMock(DateTimeFormatter::class);

        $this->extension = new CheckoutShipUntilFormExtension(
            $this->provider,
            $this->checkoutLineItemsManager,
            $this->dateTimeFormatter
        );
    }

    public function testGetExtendedType()
    {
        $this->assertSame(CheckoutShipUntilType::class, $this->extension->getExtendedType());
    }

    public function testConfigureOptionsDisabled()
    {
        $resolver = $this->prepareResolver();

        $this->provider->expects($this->atLeastOnce())
            ->method('isUpcoming')
            ->willReturn(true);
        $this->provider->expects($this->atLeastOnce())
            ->method('getAvailabilityDate')
            ->willReturn(null);

        $this->extension->configureOptions($resolver);
        $options = $resolver->resolve();
        $this->assertTrue($options['disabled']);
    }

    public function testConfigureOptionsEnabled()
    {
        $resolver = $this->prepareResolver();

        $this->provider->expects($this->atLeastOnce())
            ->method('isUpcoming')
            ->willReturn(false);

        $this->extension->configureOptions($resolver);
        $options = $resolver->resolve();
        $this->assertFalse($options['disabled']);
    }

    public function testConfigureMinDateSet()
    {
        $resolver = $this->prepareResolver();

        $date = new \DateTime();
        $this->provider->expects($this->once())
            ->method('getLatestAvailabilityDate')
            ->willReturn($date);
        $this->dateTimeFormatter->expects($this->once())
            ->method('formatDate')
            ->with($date)
            ->willReturn('01-01-2020');

        $this->extension->configureOptions($resolver);
        $options = $resolver->resolve();
        $this->assertEquals('01-01-2020', $options['minDate']);
    }

    public function testConfigureMinDateNotSet()
    {
        $resolver = $this->prepareResolver();

        $this->provider->expects($this->once())
            ->method('getLatestAvailabilityDate')
            ->willReturn(null);

        $this->extension->configureOptions($resolver);
        $options = $resolver->resolve();
        $this->assertEquals('0', $options['minDate']);
    }

    /**
     * @return OptionsResolver
     */
    protected function prepareResolver()
    {
        $checkout = new Checkout();
        $resolver = new OptionsResolver();
        $resolver->setDefault('checkout', $checkout);

        $lineItems = [
            (new OrderLineItem())->setProduct(new Product()),
            (new OrderLineItem())->setProduct(new Product()),
            (new OrderLineItem())->setProduct(new Product()),
        ];
        $this->checkoutLineItemsManager->expects($this->atLeastOnce())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        return $resolver;
    }
}
