<?php
declare(strict_types = 1);

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\EventListener\ProductAutocompleteListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;

class ProductAutocompleteListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserCurrencyManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyManager;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var ProductAutocompleteListener */
    private $listener;

    protected function setUp(): void
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->currencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->numberFormatter->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(
                static function (float $value, string $currency) {
                    return sprintf('%s %s', $currency, round($value, 2));
                }
            );

        $this->listener = new ProductAutocompleteListener($this->currencyManager, $this->numberFormatter);
    }

    public function testOnCollectAutocompleteFields(): void
    {
        $event = new CollectAutocompleteFieldsEvent([]);

        $this->listener->onCollectAutocompleteFields($event);

        $this->assertEquals(
            [
                'decimal.minimal_price_CPL_ID_CURRENCY as cpl_price',
                'decimal.minimal_price_PRICE_LIST_ID_CURRENCY as pl_price',
            ],
            $event->getFields()
        );
    }

    public function testOnProcessAutocompleteData(): void
    {
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
            ]
        );

        $this->listener->onProcessAutocompleteData($event);

        $this->assertEquals(
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
            ],
            $event->getData()
        );
    }
}
