<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Layout\DataProvider\RequestProductPricesProvider;
use Oro\Bundle\RFPBundle\Provider\RequestProductLineItemTierPricesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestProductPricesProviderTest extends TestCase
{
    private RequestProductLineItemTierPricesProvider|MockObject $requestProductLineItemTierPricesProvider;

    private RequestProductPricesProvider $requestProductPricesProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->requestProductLineItemTierPricesProvider = $this->createMock(
            RequestProductLineItemTierPricesProvider::class
        );

        $this->requestProductPricesProvider = new RequestProductPricesProvider(
            $this->requestProductLineItemTierPricesProvider
        );
    }

    public function testGetTierPrices(): void
    {
        $rfpRequest = new RFPRequest();

        $productPrice1 = $this->createMock(ProductPriceInterface::class);

        $tierPrices = [
            [
                [$productPrice1],
            ],
        ];
        $this->requestProductLineItemTierPricesProvider->expects(self::once())
            ->method('getTierPrices')
            ->willReturn($tierPrices);

        self::assertSame($tierPrices, $this->requestProductPricesProvider->getTierPrices($rfpRequest));
    }
}
