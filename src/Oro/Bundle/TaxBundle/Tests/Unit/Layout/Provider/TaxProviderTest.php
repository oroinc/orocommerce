<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Layout\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Layout\Provider\TaxProvider;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxProviderInterface;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class TaxProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxProvider */
    protected $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxProviderInterface */
    protected $taxProvider;

    protected function setUp(): void
    {
        $this->taxProvider = $this->createMock(TaxProviderInterface::class);
        $taxProviderRegistry = $this->createMock(TaxProviderRegistry::class);
        $taxProviderRegistry->expects($this->any())
            ->method('getEnabledProvider')
            ->willReturn($this->taxProvider);

        $this->provider = new TaxProvider($taxProviderRegistry);
    }

    public function testGetTax()
    {
        $value = new Order();
        $result = new Result();
        $this->taxProvider->expects($this->once())->method('loadTax')->with($value)->willReturn($result);

        $actual = $this->provider->getTax($value);

        $this->assertSame($result, $actual);
    }
}
