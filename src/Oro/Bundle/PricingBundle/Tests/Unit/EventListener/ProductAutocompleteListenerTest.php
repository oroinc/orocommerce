<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\ProductAutocompleteListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;

class ProductAutocompleteListenerTest extends \PHPUnit\Framework\TestCase
{
    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private ProductAutocompleteListener $listener;

    protected function setUp(): void
    {
        $currencyManager = $this->createMock(UserCurrencyManager::class);
        $currencyManager->expects(self::any())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $numberFormatter = $this->createMock(NumberFormatter::class);
        $numberFormatter->expects(self::any())
            ->method('formatCurrency')
            ->willReturnCallback(
                static function (float $value, string $currency) {
                    return sprintf('%s %s', $currency, round($value, 2));
                }
            );

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new ProductAutocompleteListener($currencyManager, $numberFormatter);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature_name');
    }

    /**
     * @dataProvider onCollectAutocompleteFieldsDataProvider
     */
    public function testOnCollectAutocompleteFields(
        bool $isFlatPLEnabled,
        bool $isCombinedPLEnabled,
        array $expectedFields
    ): void {
        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->withConsecutive(['oro_price_lists_flat', null], ['oro_price_lists_combined', null])
            ->willReturnOnConsecutiveCalls($isFlatPLEnabled, $isCombinedPLEnabled);

        $event = new CollectAutocompleteFieldsEvent([]);

        $this->listener->onCollectAutocompleteFields($event);

        self::assertEquals($expectedFields, $event->getFields());
    }

    public function onCollectAutocompleteFieldsDataProvider(): array
    {
        return [
            ['isFlatPLEnabled' => false, 'isCombinedPLEnabled' => false, 'expectedFields' => []],
            [
                'isFlatPLEnabled' => true,
                'isCombinedPLEnabled' => false,
                'expectedFields' => ['decimal.minimal_price_PRICE_LIST_ID_CURRENCY as pl_price'],
            ],
            [
                'isFlatPLEnabled' => false,
                'isCombinedPLEnabled' => true,
                'expectedFields' => ['decimal.minimal_price_CPL_ID_CURRENCY as cpl_price'],
            ],
            [
                'isFlatPLEnabled' => true,
                'isCombinedPLEnabled' => true,
                'expectedFields' => [
                    'decimal.minimal_price_PRICE_LIST_ID_CURRENCY as pl_price',
                    'decimal.minimal_price_CPL_ID_CURRENCY as cpl_price',
                ],
            ],
        ];
    }

    public function testOnProcessAutocompleteDataFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->withConsecutive(['oro_price_lists_flat', null], ['oro_price_lists_combined', null])
            ->willReturn(false);

        $autocompleteData = [
            'SKU1' => ['cpl_price' => null, 'pl_price' => null],
            'SKU2' => ['cpl_price' => null, 'pl_price' => ''],
            'SKU3' => ['cpl_price' => '', 'pl_price' => null],
            'SKU4' => ['cpl_price' => '', 'pl_price' => ''],
            'SKU5' => ['cpl_price' => 0, 'pl_price' => null],
            'SKU6' => ['cpl_price' => null, 'pl_price' => 0],
            'SKU7' => ['cpl_price' => 0, 'pl_price' => ''],
            'SKU8' => ['cpl_price' => '', 'pl_price' => 0],
            'SKU9' => ['cpl_price' => 10, 'pl_price' => null],
            'SKU10' => ['cpl_price' => null, 'pl_price' => 20],
            'SKU11' => ['cpl_price' => 30, 'pl_price' => ''],
            'SKU12' => ['cpl_price' => '', 'pl_price' => 40],
            'SKU13' => ['cpl_price' => 50, 'pl_price' => 60],
            'SKU14' => ['pl_price' => null],
            'SKU15' => ['pl_price' => ''],
            'SKU16' => ['pl_price' => 0],
            'SKU17' => ['pl_price' => 20],
            'SKU18' => ['cpl_price' => null],
            'SKU19' => ['cpl_price' => ''],
            'SKU20' => ['cpl_price' => 0],
            'SKU21' => ['cpl_price' => 10],
            'SKU22' => [],
        ];

        $event = new ProcessAutocompleteDataEvent($autocompleteData);

        $this->listener->onProcessAutocompleteData($event);

        self::assertSame($autocompleteData, $event->getData());
    }

    /**
     * @dataProvider processAutocompleteDataDataProvider
     */
    public function testOnProcessAutocompleteData(bool $isFlatPLEnabled, bool $isCombinedPLEnabled): void
    {
        $this->featureChecker->expects(self::atLeastOnce())
            ->method('isFeatureEnabled')
            ->withConsecutive(['oro_price_lists_flat', null], ['oro_price_lists_combined', null])
            ->willReturnOnConsecutiveCalls($isFlatPLEnabled, $isCombinedPLEnabled);

        $event = new ProcessAutocompleteDataEvent(
            [
                'SKU1' => ['cpl_price' => null, 'pl_price' => null],
                'SKU2' => ['cpl_price' => null, 'pl_price' => ''],
                'SKU3' => ['cpl_price' => '', 'pl_price' => null],
                'SKU4' => ['cpl_price' => '', 'pl_price' => ''],
                'SKU5' => ['cpl_price' => 0, 'pl_price' => null],
                'SKU6' => ['cpl_price' => null, 'pl_price' => 0],
                'SKU7' => ['cpl_price' => 0, 'pl_price' => ''],
                'SKU8' => ['cpl_price' => '', 'pl_price' => 0],
                'SKU9' => ['cpl_price' => 10, 'pl_price' => null],
                'SKU10' => ['cpl_price' => null, 'pl_price' => 20],
                'SKU11' => ['cpl_price' => 30, 'pl_price' => ''],
                'SKU12' => ['cpl_price' => '', 'pl_price' => 40],
                'SKU13' => ['cpl_price' => 50, 'pl_price' => 60],
                'SKU14' => ['pl_price' => null],
                'SKU15' => ['pl_price' => ''],
                'SKU16' => ['pl_price' => 0],
                'SKU17' => ['pl_price' => 20],
                'SKU18' => ['cpl_price' => null],
                'SKU19' => ['cpl_price' => ''],
                'SKU20' => ['cpl_price' => 0],
                'SKU21' => ['cpl_price' => 10],
                'SKU22' => [],
            ]
        );

        $this->listener->onProcessAutocompleteData($event);

        self::assertEquals(
            [
                'SKU1' => [],
                'SKU2' => [],
                'SKU3' => [],
                'SKU4' => [],
                'SKU5' => ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                'SKU6' => ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                'SKU7' => ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                'SKU8' => ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                'SKU9' => ['price' => 10, 'currency' => 'USD', 'formatted_price' => 'USD 10'],
                'SKU10' => ['price' => 20, 'currency' => 'USD', 'formatted_price' => 'USD 20'],
                'SKU11' => ['price' => 30, 'currency' => 'USD', 'formatted_price' => 'USD 30'],
                'SKU12' => ['price' => 40, 'currency' => 'USD', 'formatted_price' => 'USD 40'],
                'SKU13' => ['price' => 50, 'currency' => 'USD', 'formatted_price' => 'USD 50'],
                'SKU14' => [],
                'SKU15' => [],
                'SKU16' => ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                'SKU17' => ['price' => 20, 'currency' => 'USD', 'formatted_price' => 'USD 20'],
                'SKU18' => [],
                'SKU19' => [],
                'SKU20' => ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                'SKU21' => ['price' => 10, 'currency' => 'USD', 'formatted_price' => 'USD 10'],
                'SKU22' => [],
            ],
            $event->getData()
        );
    }

    public function processAutocompleteDataDataProvider(): array
    {
        return [
            ['isFlatPLEnabled' => true, 'isCombinedPLEnabled' => false],
            ['isFlatPLEnabled' => false, 'isCombinedPLEnabled' => true],
            ['isFlatPLEnabled' => true, 'isCombinedPLEnabled' => true],
        ];
    }
}
