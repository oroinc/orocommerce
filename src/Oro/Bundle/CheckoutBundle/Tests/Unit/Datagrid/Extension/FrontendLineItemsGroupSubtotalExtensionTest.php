<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\CheckoutBundle\Datagrid\Extension\FrontendLineItemsGroupSubtotalExtension;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class FrontendLineItemsGroupSubtotalExtensionTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private RoundingServiceInterface&MockObject $roundingService;
    private NumberFormatter&MockObject $numberFormatter;

    private FrontendLineItemsGroupSubtotalExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->extension = new FrontendLineItemsGroupSubtotalExtension(
            ['frontend-checkout-line-items-grid'],
            $this->configProvider,
            $this->roundingService,
            $this->numberFormatter
        );

        $this->extension->setParameters(new ParameterBag([]));
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(
        DatagridConfiguration $config,
        bool $isLineItemsGroupingEnabled,
        bool $expected
    ): void {
        $this->configProvider->expects(self::any())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn($isLineItemsGroupingEnabled);

        self::assertEquals($expected, $this->extension->isApplicable($config));
    }

    public function isApplicableDataProvider(): array
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-checkout-line-items-grid']);

        return [
            'unsupported datagrid' => [
                'config' => DatagridConfiguration::create(['name' => 'test']),
                'isLineItemsGroupingEnabled' => true,
                'expected' => false,
            ],
            'disabled isLineItemsGroupingEnabled option' => [
                'config' => $config,
                'isLineItemsGroupingEnabled' => false,
                'expected' => false,
            ],
            'success' => [
                'config' => $config,
                'isLineItemsGroupingEnabled' => true,
                'expected' => true,
            ],
        ];
    }

    public function testVisitResultEmptyData(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-checkout-line-items-grid']);
        $result = ResultsObject::create([]);

        $this->roundingService
            ->expects(self::once())
            ->method('round')
            ->with(0.0)
            ->willReturn(0.0);

        $this->numberFormatter
            ->expects(self::once())
            ->method('formatCurrency')
            ->with(0.0, null)
            ->willReturn('$0.00');

        $this->extension->visitResult($config, $result);

        self::assertEquals('$0.00', $result->offsetGetByPath('[metadata][groupSubtotal]'));
    }

    public function testVisitResult(): void
    {
        $config = DatagridConfiguration::create(['name' => 'frontend-checkout-line-items-grid']);
        $result = ResultsObject::create([]);
        $result->setData([
            new ResultRecord(['subtotalValue' => 123.55]),
            new ResultRecord(['currency' => 'USD', 'subtotalValue' => 55.22]),
        ]);

        $this->roundingService
            ->expects(self::once())
            ->method('round')
            ->with(178.77)
            ->willReturn(178.77);

        $this->numberFormatter
            ->expects(self::once())
            ->method('formatCurrency')
            ->with(178.77, 'USD')
            ->willReturn('$178.77');

        $this->extension->visitResult($config, $result);

        self::assertEquals('$178.77', $result->offsetGetByPath('[metadata][groupSubtotal]'));
    }
}
