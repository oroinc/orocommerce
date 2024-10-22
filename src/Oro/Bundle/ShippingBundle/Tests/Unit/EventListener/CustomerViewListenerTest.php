<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Oro\Bundle\ShippingBundle\Entity\Repository\ProductShippingOptionsRepository;
use Oro\Bundle\ShippingBundle\EventListener\FormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class CustomerViewListenerTest extends TestCase
{
    private FormViewListener $formViewListener;
    private TranslatorInterface|MockObject $translator;
    private DoctrineHelper|MockObject $doctrineHelper;
    private ProductShippingOptionsRepository|MockObject $productShippingOptionsRepository;
    private FieldAclHelper|MockObject $fieldAclHelper;
    private Environment|MockObject $twig;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())->method('trans')->willReturnArgument(0);
        $this->productShippingOptionsRepository = $this->createMock(ProductShippingOptionsRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->fieldAclHelper = $this->createMock(FieldAclHelper::class);
        $this->twig = $this->createMock(Environment::class);
        $this->formViewListener = new FormViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->createMock(RequestStack::class)
        );
        $this->formViewListener->setFieldAclHelper($this->fieldAclHelper);
    }

    /** @dataProvider onProductViewDataProvider */
    public function testOnProductView(array $data, array $expectedResult): void
    {
        $scrollData = new ScrollData();
        $product = new Product();
        $product
            ->setType($data['productType'])
            ->setSku($data['sku']);

        if ($data['productType'] === Product::TYPE_KIT) {
            $product->setKitShippingCalculationMethod($data['kitShippingCalculationMethod']);
        }

        $event = new BeforeListRenderEvent($this->twig, $scrollData, $product);

        $this->productShippingOptionsRepository
            ->expects(self::once())
            ->method('findBy')
            ->willReturn([]);

        $this->doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with('OroShippingBundle:ProductShippingOptions')
            ->willReturn($this->productShippingOptionsRepository);

        $this->fieldAclHelper
            ->expects(self::once())
            ->method('isFieldViewGranted')
            ->with($product, 'unitPrecisions')
            ->willReturn($data['isFieldViewGranted']);

        $this->twig
            ->expects($data['isFieldViewGranted'] ? self::once() : self::never())
            ->method('render')
            ->willReturnCallback(function (string $template, array $parameters) use ($product): string {
                self::assertEquals([
                    'entity' => $product,
                    'shippingOptions' => [],
                    'kitShippingCalculationMethodValue' => $product->isKit() ? sprintf(
                        'oro.product.kit_shipping_calculation_method.choices.%s',
                        $product->getKitShippingCalculationMethod()
                    ) : null
                ], $parameters);

                return sprintf(
                    '%s_%s_%s',
                    $parameters['entity']?->getSku(),
                    implode('+', $parameters['shippingOptions']) ?: 'null',
                    $parameters['kitShippingCalculationMethodValue'] ?? 'null'
                );
            });

        $this->formViewListener->onProductView($event);

        self::assertEquals($expectedResult, $event->getScrollData()->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onProductViewDataProvider(): array
    {
        return [
            'Simple product without fieldView permissions' => [
                'data' => [
                    'sku' => 'SSKU-1',
                    'isFieldViewGranted' => false,
                    'productType' => Product::TYPE_SIMPLE,
                ],
                'expectedResult' => []
            ],
            'Simple product with fieldView permissions' => [
                'data' => [
                    'sku' => 'SSKU-2',
                    'isFieldViewGranted' => true,
                    'productType' => Product::TYPE_SIMPLE,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => ['SSKU-2_null_null']
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
            'Product kit without fieldView permissions' => [
                'data' => [
                    'sku' => 'KSKU-1',
                    'isFieldViewGranted' => false,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ALL
                ],
                'expectedResult' => []
            ],
            'Product kit with fieldView permissions and product kit shipping all calculation method' => [
                'data' => [
                    'sku' => 'KSKU-2',
                    'isFieldViewGranted' => true,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ALL,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => [
                                        'KSKU-2'.
                                        '_null_'.
                                        'oro.product.kit_shipping_calculation_method.choices.kit_shipping_all'
                                    ]
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
            'Product kit with fieldView permissions and product kit shipping only product calculation method' => [
                'data' => [
                    'sku' => 'KSKU-3',
                    'isFieldViewGranted' => true,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ONLY_PRODUCT,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => [
                                        'KSKU-3'.
                                        '_null_'.
                                        'oro.product.kit_shipping_calculation_method.choices.kit_shipping_product'
                                    ]
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
            'Product kit with fieldView permissions and product kit shipping only items calculation method' => [
                'data' => [
                    'sku' => 'KSKU-4',
                    'isFieldViewGranted' => true,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ONLY_ITEMS,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => [
                                        'KSKU-4'.
                                        '_null_'.
                                        'oro.product.kit_shipping_calculation_method.choices.kit_shipping_items'
                                    ]
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
        ];
    }

    /** @dataProvider onProductEditDataProvider */
    public function testOnProductEdit(array $data, array $expectedResult): void
    {
        $formView = new FormView();
        $formView->children['data'] = $data;
        $scrollData = new ScrollData();
        $product = new Product();
        $product
            ->setType($data['productType'])
            ->setSku($data['sku']);

        if ($data['productType'] === Product::TYPE_KIT) {
            $product->setKitShippingCalculationMethod($data['kitShippingCalculationMethod']);
        }

        $event = new BeforeListRenderEvent($this->twig, $scrollData, $product, $formView);

        $this->fieldAclHelper
            ->expects(self::once())
            ->method('isFieldAvailable')
            ->with($product, 'unitPrecisions')
            ->willReturn($data['isFieldAvailable']);

        $this->twig
            ->expects(self::any())
            ->method('render')
            ->willReturnCallback(
                function (string $template, array $parameters) use ($product, $formView, $data): string {
                    self::assertEquals([
                        'form' => $formView,
                        'isKit' => $product->isKit(),
                        'isShippingOptionsFieldAvailable' => $data['isFieldAvailable']
                    ], $parameters);

                    return sprintf(
                        '%s_%s_%s',
                        $data['sku'],
                        $product->isKit() ? 'kit' : 'not_kit',
                        $data['isFieldAvailable'] ? 'field_available' : 'field_not_available'
                    );
                }
            );

        $this->formViewListener->onProductEdit($event);

        self::assertEquals($expectedResult, $event->getScrollData()->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onProductEditDataProvider(): array
    {
        return [
            'Simple product with not available shipping options field' => [
                'data' => [
                    'sku' => 'SSKU-1',
                    'isFieldAvailable' => false,
                    'productType' => Product::TYPE_SIMPLE,
                ],
                'expectedResult' => []
            ],
            'Simple product with available shipping options field' => [
                'data' => [
                    'sku' => 'SSKU-2',
                    'isFieldAvailable' => true,
                    'productType' => Product::TYPE_SIMPLE,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => ['SSKU-2_not_kit_field_available']
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
            'Product kit with not available shipping options field' => [
                'data' => [
                    'sku' => 'KSKU-1',
                    'isFieldAvailable' => false,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ALL
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => ['KSKU-1_kit_field_not_available']
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
            'Product kit with available shipping options field and product kit and shipping all method' => [
                'data' => [
                    'sku' => 'KSKU-2',
                    'isFieldAvailable' => true,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ALL,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => ['KSKU-2_kit_field_available']
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
            'Product kit with available shipping options field and product kit and shipping only product method' => [
                'data' => [
                    'sku' => 'KSKU-3',
                    'isFieldAvailable' => true,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ONLY_PRODUCT,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => ['KSKU-3_kit_field_available']
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
            'Product kit with available shipping options field and product kit and shipping only items method' => [
                'data' => [
                    'sku' => 'KSKU-4',
                    'isFieldAvailable' => true,
                    'productType' => Product::TYPE_KIT,
                    'kitShippingCalculationMethod' => Product::KIT_SHIPPING_ONLY_ITEMS,
                ],
                'expectedResult' => [
                    'dataBlocks' => [
                        FormViewListener::SHIPPING_BLOCK_NAME => [
                            'subblocks' => [
                                [
                                    'data' => ['KSKU-4_kit_field_available']
                                ]
                            ],
                            'title' => FormViewListener::SHIPPING_BLOCK_LABEL,
                            'priority' => FormViewListener::SHIPPING_BLOCK_PRIORITY,
                            'useSubBlockDivider' => true,
                        ]
                    ]
                ]
            ],
        ];
    }
}
