<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\EventListener\EntityTaxListener;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class EntityTaxListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BuiltInTaxProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxProvider;

    /** @var EntityTaxListener */
    private $listener;

    protected function setUp(): void
    {
        $this->taxProvider = $this->createMock(BuiltInTaxProvider::class);

        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->listener = new EntityTaxListener($taxProviderRegistry, Order::class);
    }

    public function testPreRemove()
    {
        $order = new Order();
        $this->taxProvider->expects($this->once())
            ->method('removeTax')
            ->with($order);

        $this->listener->preRemove($order);
    }

    public function testPreRemoveWithDisabledTaxesCatchException()
    {
        $order = new Order();
        $this->taxProvider->expects($this->once())
            ->method('removeTax')
            ->with($order)
            ->willThrowException(new TaxationDisabledException());

        $this->listener->preRemove($order);
    }
}
