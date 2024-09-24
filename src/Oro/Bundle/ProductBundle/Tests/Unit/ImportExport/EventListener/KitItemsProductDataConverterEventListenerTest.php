<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\EventListener;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;
use Oro\Bundle\ProductBundle\ImportExport\EventListener\KitItemsProductDataConverterEventListener;

class KitItemsProductDataConverterEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private KitItemsProductDataConverterEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new KitItemsProductDataConverterEventListener('|');
    }

    /**
     * @dataProvider onConvertToImportDataProvider
     */
    public function testOnConvertToImport(array $expected, array $data): void
    {
        $event = new ProductDataConverterEvent($data);

        $this->listener->onConvertToImport($event);

        self::assertEquals($expected, $event->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onConvertToImportDataProvider(): array
    {
        $kitItems = <<<EOF
id=3,label="Receipt Printer(s)",optional=false,products=8DO33,min_qty=2,max_qty=2,unit=item
ID=,label="\"Barcode Scanner\"",optional=true,products=,min_qty=2,max_qty=1,unit=
Label=",My, =Escaped= \"Kit\" \'Item\'",oPtiOnaL=false,products=5TJ23|2RW93,min_qty=,max_qty=,unit=set
EOF;
        $kitItems2 = <<<EOF
id=3,label="Receipt Printer(s)",optional=0,products=8DO33,min_qty=2,max_qty=2,unit=item
ID=,label="\"Barcode Scanner\"",optional=True,products=,min_qty=2,max_qty=1,unit=
ID=,label="\"Barcode\"",optional=YES,products=,min_qty=2,max_qty=1,unit=
EOF;

        return [
            'Without kitItems key' => [
                'expected' => ['test' => true],
                'data' => ['test' => true],
            ],
            'KitItems field is empty string' => [
                'expected' => ['test' => true, 'kitItems' => ''],
                'data' => ['test' => true, 'kitItems' => ''],
            ],
            'KitItems field is null' => [
                'expected' => ['test' => true, 'kitItems' => null],
                'data' => ['test' => true, 'kitItems' => null],
            ],
            'KitItems has uppercase columns' => [
                'expected' => [
                    'test' => true,
                    'kitItems' => [
                        [
                            'sortOrder' => 0,
                            'id' => 3,
                            'labels' => [LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => 'Receipt']],
                            'optional' => false,
                            'kitItemProducts' => [
                                ['product' => ['sku' => '8DO33']],
                            ],
                            'maximumQuantity' => 2.0,
                            'minimumQuantity' => 2.0,
                            'productUnit' => ['code' => 'item'],
                        ],
                    ],
                ],
                'data' => [
                    'test' => true,
                    'kitItems' => 'ID=3,label=Receipt,OPTIONAL=false,ProduCTS=8DO33,min_qty=2,max_qty=2,unit=item',
                ],
            ],
            'With kitItems field' => [
                'expected' => [
                    'test' => true,
                    'kitItems' => [
                        [
                            'sortOrder' => 0,
                            'id' => 3,
                            'labels' => [
                                LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => 'Receipt Printer(s)'],
                            ],
                            'optional' => false,
                            'kitItemProducts' => [
                                ['product' => ['sku' => '8DO33']],
                            ],
                            'maximumQuantity' => 2.0,
                            'minimumQuantity' => 2.0,
                            'productUnit' => ['code' => 'item'],
                        ],
                        [
                            'sortOrder' => 1,
                            'labels' => [
                                LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => '"Barcode Scanner"'],
                            ],
                            'optional' => true,
                            'maximumQuantity' => 1.0,
                            'minimumQuantity' => 2.0,
                        ],
                        [
                            'sortOrder' => 2,
                            'labels' => [
                                LocalizationCodeFormatter::DEFAULT_LOCALIZATION => [
                                    'string' => ',My, =Escaped= "Kit" \'Item\'',
                                ],
                            ],
                            'optional' => false,
                            'kitItemProducts' => [
                                ['product' => ['sku' => '5TJ23']],
                                ['product' => ['sku' => '2RW93']],
                            ],
                            'productUnit' => ['code' => 'set'],
                        ],
                    ],
                ],
                'data' => ['test' => true, 'kitItems' => $kitItems],
            ],
            'KitItems has uppercase and integer optional parameters' => [
                'expected' => [
                    'test' => true,
                    'kitItems' => [
                        [
                            'sortOrder' => 0,
                            'id' => 3,
                            'labels' => [
                                LocalizationCodeFormatter::DEFAULT_LOCALIZATION => [
                                    'string' => 'Receipt Printer(s)',
                                ],
                            ],
                            'optional' => false,
                            'kitItemProducts' => [
                                ['product' => ['sku' => '8DO33']],
                            ],
                            'maximumQuantity' => 2.0,
                            'minimumQuantity' => 2.0,
                            'productUnit' => ['code' => 'item'],
                        ],
                        [
                            'sortOrder' => 1,
                            'labels' => [
                                LocalizationCodeFormatter::DEFAULT_LOCALIZATION => [
                                    'string' => '"Barcode Scanner"',
                                ],
                            ],
                            'optional' => true,
                            'maximumQuantity' => 1.0,
                            'minimumQuantity' => 2.0,
                        ],
                        [
                            'sortOrder' => 2,
                            'labels' => [
                                LocalizationCodeFormatter::DEFAULT_LOCALIZATION => [
                                    'string' => '"Barcode"',
                                ],
                            ],
                            'optional' => true,
                            'maximumQuantity' => 1.0,
                            'minimumQuantity' => 2.0,
                        ],
                    ],
                ],
                'data' => ['test' => true, 'kitItems' => $kitItems2],
            ],
            'KitItems has label with wrong quotes' => [
                'expected' => [
                    'test' => true,
                    'kitItems' => [
                        [
                            'sortOrder' => 0,
                            'labels' => [LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => 'Receipt']],
                            'optional' => false,
                            'kitItemProducts' => [
                                ['product' => ['sku' => '8DO33']],
                            ],
                            'maximumQuantity' => 2.0,
                            'minimumQuantity' => 2.0,
                            'productUnit' => ['code' => 'item'],
                        ],
                    ],
                ],
                'data' => [
                    'test' => true,
                    'kitItems' => 'label=“Receipt",optional=0,products=8DO33,min_qty=2,max_qty=2,unit=item',
                ],
            ],
            'KitItems has empty label' => [
                'expected' => [
                    'test' => true,
                    'kitItems' => [
                        [
                            'sortOrder' => 0,
                            'labels' => [LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => '']],
                            'optional' => false,
                            'kitItemProducts' => [
                                ['product' => ['sku' => '8DO33']],
                            ],
                            'maximumQuantity' => 2.0,
                            'minimumQuantity' => 2.0,
                            'productUnit' => ['code' => 'item'],
                        ],
                    ],
                ],
                'data' => [
                    'test' => true,
                    'kitItems' => 'label=" ",optional=0,products=8DO33,min_qty=2,max_qty=2,unit=item',
                ],
            ],
            'KitItems has duplicated product skus' => [
                'expected' => [
                    'test' => true,
                    'kitItems' => [
                        [
                            'sortOrder' => 0,
                            'labels' => [LocalizationCodeFormatter::DEFAULT_LOCALIZATION => ['string' => 'Receipt']],
                            'optional' => false,
                            'kitItemProducts' => [
                                ['product' => ['sku' => '8DO33']],
                                ['product' => ['sku' => '5TJ23']],
                            ],
                            'maximumQuantity' => 2.0,
                            'minimumQuantity' => 2.0,
                            'productUnit' => ['code' => 'item'],
                        ],
                    ],
                ],
                'data' => [
                    'test' => true,
                    'kitItems' => 'label="Receipt",optional=0,products=8DO33|5TJ23|8DO33,min_qty=2,max_qty=2,unit=item',
                ],
            ],
        ];
    }

    public function testOnConvertToImportWithExtraField(): void
    {
        $context = new Context([]);
        $data = ['kitItems' => 'id=3,label="Receipt Printer(s)",quantity=1'];

        $event = new ProductDataConverterEvent($data);
        $event->setContext($context);

        $this->listener->onConvertToImport($event);

        self::assertEquals(
            [0 => ['quantity']],
            $context->getValue(KitItemsProductDataConverterEventListener::KIT_ITEMS_EXTRA_FIELDS)
        );
    }

    public function testOnConvertToImportWithWrongParameters(): void
    {
        $kitItems = <<<EOF
label="Receipt Printer(s)",optional=invalid,products=8DO33,min_qty=2,max_qty=2,unit=item
id=1.0,label="Barcode Scanner",optional=yes,products=,min_qty=2,max_qty=1,unit=
id="text value",label="Scanner",optional=no,products=,min_qty=2,max_qty=1,unit=
label="Barcode",optional=1,products=,min_qty=text,max_qty="100,00",unit=
EOF;
        $context = new Context([]);
        $data = ['kitItems' => $kitItems];

        $event = new ProductDataConverterEvent($data);
        $event->setContext($context);

        $this->listener->onConvertToImport($event);

        $expected = [
            0 => ['optional' => 'invalid'],
            1 => ['id' => 1.0],
            2 => ['id' => '"text value"'],
            3 => ['min_qty' => 'text', 'max_qty' => '"100,00"'],
        ];

        self::assertEquals(
            $expected,
            $context->getValue(KitItemsProductDataConverterEventListener::KIT_ITEMS_INVALID_VALUES)
        );
    }
}
