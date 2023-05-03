<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\ProductAutocompleteListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

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
                'expectedFields' => ['decimal.minimal_price.PRICE_LIST_ID_CURRENCY as pl_price'],
            ],
            [
                'isFlatPLEnabled' => false,
                'isCombinedPLEnabled' => true,
                'expectedFields' => ['decimal.minimal_price.CPL_ID_CURRENCY as cpl_price'],
            ],
            [
                'isFlatPLEnabled' => true,
                'isCombinedPLEnabled' => true,
                'expectedFields' => [
                    'decimal.minimal_price.PRICE_LIST_ID_CURRENCY as pl_price',
                    'decimal.minimal_price.CPL_ID_CURRENCY as cpl_price',
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
            'products' => [
                ['cpl_price' => null, 'pl_price' => null],
                ['cpl_price' => null, 'pl_price' => ''],
                ['cpl_price' => '', 'pl_price' => null],
                ['cpl_price' => '', 'pl_price' => ''],
                ['cpl_price' => 0, 'pl_price' => null],
                ['cpl_price' => null, 'pl_price' => 0],
                ['cpl_price' => 0, 'pl_price' => ''],
                ['cpl_price' => '', 'pl_price' => 0],
                ['cpl_price' => 10, 'pl_price' => null],
                ['cpl_price' => null, 'pl_price' => 20],
                ['cpl_price' => 30, 'pl_price' => ''],
                ['cpl_price' => '', 'pl_price' => 40],
                ['cpl_price' => 50, 'pl_price' => 60],
                ['pl_price' => null],
                ['pl_price' => ''],
                ['pl_price' => 0],
                ['pl_price' => 20],
                ['cpl_price' => null],
                ['cpl_price' => ''],
                ['cpl_price' => 0],
                ['cpl_price' => 10],
                [],
            ]
        ];

        $event = new ProcessAutocompleteDataEvent($autocompleteData, 'request', new Result(new Query()));

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
                'products' => [
                    ['cpl_price' => null, 'pl_price' => null],
                    ['cpl_price' => null, 'pl_price' => ''],
                    ['cpl_price' => '', 'pl_price' => null],
                    ['cpl_price' => '', 'pl_price' => ''],
                    ['cpl_price' => 0, 'pl_price' => null],
                    ['cpl_price' => null, 'pl_price' => 0],
                    ['cpl_price' => 0, 'pl_price' => ''],
                    ['cpl_price' => '', 'pl_price' => 0],
                    ['cpl_price' => 10, 'pl_price' => null],
                    ['cpl_price' => null, 'pl_price' => 20],
                    ['cpl_price' => 30, 'pl_price' => ''],
                    ['cpl_price' => '', 'pl_price' => 40],
                    ['cpl_price' => 50, 'pl_price' => 60],
                    ['pl_price' => null],
                    ['pl_price' => ''],
                    ['pl_price' => 0],
                    ['pl_price' => 20],
                    ['cpl_price' => null],
                    ['cpl_price' => ''],
                    ['cpl_price' => 0],
                    ['cpl_price' => 10],
                    [],
                ]
            ],
            'request',
            new Result(new Query())
        );

        $this->listener->onProcessAutocompleteData($event);

        self::assertEquals(
            [
                'products' => [
                    [],
                    [],
                    [],
                    [],
                    ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                    ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                    ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                    ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                    ['price' => 10, 'currency' => 'USD', 'formatted_price' => 'USD 10'],
                    ['price' => 20, 'currency' => 'USD', 'formatted_price' => 'USD 20'],
                    ['price' => 30, 'currency' => 'USD', 'formatted_price' => 'USD 30'],
                    ['price' => 40, 'currency' => 'USD', 'formatted_price' => 'USD 40'],
                    ['price' => 50, 'currency' => 'USD', 'formatted_price' => 'USD 50'],
                    [],
                    [],
                    ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                    ['price' => 20, 'currency' => 'USD', 'formatted_price' => 'USD 20'],
                    [],
                    [],
                    ['price' => 0, 'currency' => 'USD', 'formatted_price' => 'USD 0'],
                    ['price' => 10, 'currency' => 'USD', 'formatted_price' => 'USD 10'],
                    [],
                ]
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
