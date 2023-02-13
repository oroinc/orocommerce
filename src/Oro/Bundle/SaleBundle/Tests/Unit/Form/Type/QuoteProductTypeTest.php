<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductRequestType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuoteProductTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /** @var QuoteProductType */
    protected $formType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var ProductUnitRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->repository = $this->createMock(ProductUnitRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);
        $productUnitLabelFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(function ($unitCode, $isShort) {
                return $unitCode . '-formatted-' . ($isShort ? 'short' : 'full');
            });
        $productUnitLabelFormatter->expects($this->any())
            ->method('formatChoices')
            ->willReturnCallback(function ($units, $isShort) {
                return array_map(function ($unit) use ($isShort) {
                    return $unit . '-formatted2-' . ($isShort ? 'short' : 'full');
                }, $units);
            });

        $this->configureQuoteProductOfferFormatter();

        $this->formType = new QuoteProductType(
            $this->translator,
            $productUnitLabelFormatter,
            $this->quoteProductFormatter,
            $doctrine
        );
        $this->formType->setDataClass(QuoteProduct::class);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);
                $this->assertArrayHasKey('page_component', $options);
                $this->assertArrayHasKey('page_component_options', $options);
                $this->assertArrayHasKey('allow_add_free_form_items', $options);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData, array $expectedData)
    {
        $this->repository->expects($this->once())
            ->method('getAllUnits')
            ->willReturn($inputData['allUnits']);

        $view = new FormView();

        $view->vars = $inputData['vars'];

        $form = $this->createMock(FormInterface::class);

        $this->formType->finishView($view, $form, $inputData['options']);

        $this->assertEquals($expectedData, $view->vars);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function finishViewProvider(): array
    {
        $defaultOptions = [
            'compact_units' => true,
            'allow_add_free_form_items' => true,
        ];

        return [
            'empty quote product' => [
                'input'     => [
                    'vars' => [
                        'value' => null,
                    ],
                    'allUnits' => [],
                    'options' => [
                        'compact_units' => false,
                        'allow_add_free_form_items' => false,
                    ],
                ],
                'expected'  => [
                    'value' => null,
                    'componentOptions' => [
                        'units' => [],
                        'allUnits'          => [],
                        'typeOffer'         => QuoteProduct::TYPE_OFFER,
                        'typeReplacement'   => QuoteProduct::TYPE_NOT_AVAILABLE,
                        'compactUnits' => false,
                        'isFreeForm' => false,
                        'allowEditFreeForm' => false,
                    ],
                ],
            ],
            'empty product and replacement' => [
                'input'     => [
                    'vars' => [
                        'value' => new QuoteProduct(),
                    ],
                    'allUnits' => [
                        'unit10'
                    ],
                    'options' => [
                        'compact_units' => false,
                        'allow_add_free_form_items' => true,
                    ],
                ],
                'expected'  => [
                    'value' => new QuoteProduct(),
                    'componentOptions' => [
                        'units' => [],
                        'allUnits' => [
                            'unit10-formatted2-full',
                        ],
                        'typeOffer' => QuoteProduct::TYPE_OFFER,
                        'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                        'compactUnits' => false,
                        'isFreeForm' => false,
                        'allowEditFreeForm' => true,
                    ],
                ],
            ],
            'existing product and replacement' => [
                'input'     => [
                    'vars' => [
                        'value' => (new QuoteProduct())
                            ->setProduct($this->createProduct(1, ['unit1' => 0, 'unit2' => 2]))
                            ->setProductReplacement($this->createProduct(2, ['unit2' => 2, 'unit3' => 0])),
                    ],
                    'allUnits' => [
                        'unit20',
                        'unit30',
                    ],
                    'options' => [
                        'compact_units' => false,
                        'allow_add_free_form_items' => true,
                    ],
                ],
                'expected'  => [
                    'value' => (new QuoteProduct())
                        ->setProduct($this->createProduct(1, ['unit1' => 0, 'unit2' => 2]))
                        ->setProductReplacement($this->createProduct(2, ['unit2' => 2, 'unit3' => 0])),
                    'componentOptions' => [
                        'units' => [
                            1 => [
                                'unit1' => 0,
                                'unit2' => 2,
                            ],
                            2 => [
                                'unit2' => 2,
                                'unit3' => 0,
                            ],
                        ],
                        'allUnits' => [
                            'unit20-formatted2-full',
                            'unit30-formatted2-full',
                        ],
                        'typeOffer' => QuoteProduct::TYPE_OFFER,
                        'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                        'compactUnits' => false,
                        'allowEditFreeForm' => true,
                        'isFreeForm' => false,
                    ],
                ],
            ],
            'existing product and replacement and compact units' => [
                'input'     => [
                    'vars' => [
                        'value' => (new QuoteProduct())
                            ->setProduct($this->createProduct(3, ['unit3' => 2, 'unit4' => 0]))
                            ->setProductReplacement($this->createProduct(4, ['unit4' => 0, 'unit5' => 2])),
                    ],
                    'allUnits' => [
                        'unit3',
                        'unit4',
                    ],
                    'options' => $defaultOptions,
                ],
                'expected'  => [
                    'value' => (new QuoteProduct())
                        ->setProduct($this->createProduct(3, ['unit3' => 2, 'unit4' => 0]))
                        ->setProductReplacement($this->createProduct(4, ['unit4' => 0, 'unit5' => 2])),
                    'componentOptions' => [
                        'units' => [
                            3 => [
                                'unit3' => 2,
                                'unit4' => 0,
                            ],
                            4 => [
                                'unit4' => 0,
                                'unit5' => 2,
                            ],
                        ],
                        'allUnits' => [
                            'unit3-formatted2-short',
                            'unit4-formatted2-short',
                        ],
                        'typeOffer' => QuoteProduct::TYPE_OFFER,
                        'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                        'compactUnits' => true,
                        'allowEditFreeForm' => true,
                        'isFreeForm' => false,
                    ],
                ],
            ],
            'product free form' => [
                'input'     => [
                    'vars' => [
                        'value' => (new QuoteProduct())
                            ->setFreeFormProduct('free form title'),
                    ],
                    'allUnits' => [
                        'unit3',
                        'unit4',
                    ],
                    'options' => $defaultOptions,
                ],
                'expected'  => [
                    'value' => (new QuoteProduct())
                        ->setFreeFormProduct('free form title'),
                    'componentOptions' => [
                        'units' => [],
                        'allUnits' => [
                            'unit3-formatted2-short',
                            'unit4-formatted2-short',
                        ],
                        'typeOffer' => QuoteProduct::TYPE_OFFER,
                        'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                        'compactUnits' => true,
                        'allowEditFreeForm' => true,
                        'isFreeForm' => true,
                    ],
                ],
            ],
            'replacement free form' => [
                'input'     => [
                    'vars' => [
                        'value' => (new QuoteProduct())
                            ->setFreeFormProductReplacement('free form title'),
                    ],
                    'allUnits' => [
                        'unit3',
                        'unit4',
                    ],
                    'options' => $defaultOptions,
                ],
                'expected'  => [
                    'value' => (new QuoteProduct())
                        ->setFreeFormProductReplacement('free form title'),
                    'componentOptions' => [
                        'units' => [],
                        'allUnits' => [
                            'unit3-formatted2-short',
                            'unit4-formatted2-short',
                        ],
                        'typeOffer' => QuoteProduct::TYPE_OFFER,
                        'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
                        'compactUnits' => true,
                        'allowEditFreeForm' => true,
                        'isFreeForm' => true,
                    ],
                ],
            ],
        ];
    }

    private function createProduct(int $id, array $units = []): Product
    {
        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getId')
            ->willReturn($id);
        $product->expects(self::any())
            ->method('getAvailableUnitCodes')
            ->willReturn(array_keys($units));
        $product->expects(self::any())
            ->method('getSellUnitsPrecision')
            ->willReturn($units);

        return $product;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider(): array
    {
        $quoteProductOffer = $this->getQuoteProductOffer(2, 10, 'kg', self::QPO_PRICE_TYPE1, Price::create(20, 'USD'));

        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [
                ],
                'expectedData'  => $this->getQuoteProduct(2)->setProduct(null),
                'inputData'     => $this->getQuoteProduct(2)->setProduct(null),
            ],
            'empty quote' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => 2,
                    'type'      => self::QP_TYPE1,
                    'comment'   => 'comment1',
                    'commentCustomer' => 'comment2',
                    'quoteProductOffers' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'priceType'     => self::QPO_PRICE_TYPE1,
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData' => $this->getQuoteProduct(
                    2,
                    self::QP_TYPE1,
                    'comment1',
                    'comment2',
                    [],
                    [
                        clone $quoteProductOffer,
                    ]
                )->setQuote(null),
                'inputData' => $this->getQuoteProduct(2)->setQuote(null)->setProduct(null),
            ],
            'empty product' => [
                'isValid'       => false,
                'submittedData' => [
                    'type'      => self::QP_TYPE1,
                    'comment'   => 'comment1',
                    'commentCustomer' => 'comment2',
                    'quoteProductOffers' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'priceType'     => self::QPO_PRICE_TYPE1,
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData' => $this->getQuoteProduct(
                    2,
                    self::QP_TYPE1,
                    'comment1',
                    'comment2',
                    [],
                    [
                        clone $quoteProductOffer,
                    ]
                )->setProduct(null),
                'inputData' => $this->getQuoteProduct(2)->setProduct(null),
            ],
            'empty type' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment1',
                    'commentCustomer' => 'comment2',
                    'quoteProductOffers' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'priceType'     => self::QPO_PRICE_TYPE1,
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData' => $this->getQuoteProduct(
                    2,
                    null,
                    'comment1',
                    'comment2',
                    [],
                    [
                        clone $quoteProductOffer,
                    ]
                ),
                'inputData' => $this->getQuoteProduct(2)->setProduct(null),
            ],
            'empty offers' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment1',
                    'commentCustomer' => 'comment2',
                ],
                'expectedData'  => $this->getQuoteProduct(2, null, 'comment1', 'comment2', [], []),
                'inputData'     => $this->getQuoteProduct(2)->setProduct(null),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'product'   => 2,
                    'type'      => self::QP_TYPE1,
                    'comment'   => 'comment1',
                    'commentCustomer' => 'comment2',
                    'quoteProductOffers' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'priceType'     => self::QPO_PRICE_TYPE1,
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData' => $this->getQuoteProduct(
                    2,
                    self::QP_TYPE1,
                    'comment1',
                    'comment2',
                    [],
                    [
                        clone $quoteProductOffer,
                    ]
                ),
                'inputData' => $this->getQuoteProduct(2)->setProduct(null),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    ProductSelectType::class => new ProductSelectTypeStub(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    $this->preparePriceType(),
                    EntityType::class => $this->prepareProductEntityType(),
                    QuoteProductOfferType::class => $this->prepareQuoteProductOfferType(),
                    QuoteProductRequestType::class => $this->prepareQuoteProductRequestType(),
                    ProductUnitSelectionType::class => $this->prepareProductUnitSelectionType(),
                    $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
