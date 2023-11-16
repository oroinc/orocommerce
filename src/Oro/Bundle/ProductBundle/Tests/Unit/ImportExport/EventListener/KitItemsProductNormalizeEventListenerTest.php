<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;
use Oro\Bundle\ProductBundle\ImportExport\EventListener\KitItemsProductNormalizeEventListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class KitItemsProductNormalizeEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private KitItemsProductNormalizeEventListener $listener;

    protected function setUp(): void
    {
        $this->listener = new KitItemsProductNormalizeEventListener('|');
    }

    /**
     * @dataProvider onNormalizeDataProvider
     */
    public function testOnNormalize(array $expected, array $data): void
    {
        $event = new ProductNormalizerEvent(new ProductStub(), $data);

        $this->listener->onNormalize($event);

        self::assertEquals($expected, $event->getPlainData());
    }

    public function onNormalizeDataProvider(): array
    {
        $kitItems = <<<EOF
id=3,label="Receipt Printer(s)",optional=false,products=8DO33,min_qty=2,max_qty=2,unit=item
id=22,label="\"Barcode Scanner\"",optional=true,products=,min_qty=2,max_qty=1,unit=
id=1,label=",My, =Escaped= \"Kit\" \'Item\'",optional=false,products=5TJ23|2RW93,min_qty=,max_qty=,unit=set
EOF;

        return [
            'Without kitItems key' => [
                'expected' => ['test' => true],
                'data' => ['test' => true]
            ],
            'With kitItems key' => [
                'expected' => ['test' => true, 'kitItems' => $kitItems],
                'data' => [
                    'test' => true,
                    'kitItems' => [
                        [
                            'id' => 1,
                            'maximumQuantity' => null,
                            'minimumQuantity' => null,
                            'optional' => false,
                            'sortOrder' => 4,
                            'kitItemProducts' => [
                                ['id' => 1, 'product' => ['sku' => '5TJ23']],
                                ['id' => 2, 'product' => ['sku' => '2RW93']],
                            ],
                            'labels' => [
                                ['localization' => null, 'string' => ",My, =Escaped= \"Kit\" 'Item'"],
                                ['localization' => ['name' => 'English (United States)'], 'string' => null]
                            ],
                            'productUnit' => ['code' => 'set'],
                        ],
                        [
                            'id' => 22,
                            'maximumQuantity' => 1.0,
                            'minimumQuantity' => 2.0,
                            'optional' => true,
                            'sortOrder' => 3,
                            'kitItemProducts' => [],
                            'labels' => [
                                ['localization' => null, 'string' => '"Barcode Scanner"'],
                                ['localization' => ['name' => 'English (United States)'], 'string' => null]
                            ],
                            'productUnit' => null,
                        ],
                        [
                            'id' => 3,
                            'maximumQuantity' => 2.0,
                            'minimumQuantity' => 2.0,
                            'optional' => false,
                            'sortOrder' => 1,
                            'kitItemProducts' => [
                                ['id' => 5, 'product' => ['sku' => '8DO33']]
                            ],
                            'labels' => [
                                ['localization' => null, 'string' => 'Receipt Printer(s)'],
                                ['localization' => ['name' => 'English (United States)'], 'string' => null]
                            ],
                            'productUnit' => ['code' => 'item'],
                        ]
                    ]
                ]
            ],
        ];
    }
}
