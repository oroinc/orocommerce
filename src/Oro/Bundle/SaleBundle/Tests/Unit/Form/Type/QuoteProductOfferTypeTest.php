<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteProductOfferTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /**
     * @var QuoteProductOfferType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configureQuoteProductOfferFormatter();
        $this->formType = new QuoteProductOfferType($this->quoteProductOfferFormatter);
        $this->formType->setDataClass('Oro\Bundle\SaleBundle\Entity\QuoteProductOffer');
        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit\Framework\MockObject\MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);

                return true;
            }))
        ;

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider postSetDataProvider
     */
    public function testPostSetData(QuoteProductOffer $inputData, array $expectedData = [])
    {
        $form = $this->factory->create(QuoteProductOfferType::class, $inputData);

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $form->get($key)->getData(), $key);
        }
    }

    /**
     * @return array
     */
    public function postSetDataProvider()
    {
        return [
            'empty values' => [
                'input' => new QuoteProductOffer(),
                'expected' => [
                    'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                    'quantity' => 1,
                ],
            ],
            'existing values' => [
                'input' => (new QuoteProductOffer())
                    ->setPriceType(QuoteProductOffer::PRICE_TYPE_BUNDLED)
                    ->setQuantity(10)
                    ->setAllowIncrements(false),
                'expected' => [
                    'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                    'quantity' => 10,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getQuoteProductOffer(1, 1),
                'defaultData'   => $this->getQuoteProductOffer(1),
            ],
            'empty quote product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(2, 88, 'kg', self::QPO_PRICE_TYPE1, $this->createPrice(99, 'EUR'))
                    ->setQuoteProduct(null),
                'defaultData'   => $this->getQuoteProductOffer(2)
                    ->setQuoteProduct(null),
            ],
            'empty quantity' => [
                'isValid'       => true,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 11,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(3, 1, 'kg', self::QPO_PRICE_TYPE1, $this->createPrice(11, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(3),
            ],
            'empty price type' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 88,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 99,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this->getQuoteProductOffer(4, 88, 'kg', null, $this->createPrice(99, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(4),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 22,
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 33,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(5, 22, null, self::QPO_PRICE_TYPE1, $this->createPrice(33, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(5),
            ],
            'empty price' => [
                'isValid'       => true, //Quote can be create with empty price
                'submittedData' => [
                    'quantity'      => 44,
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                ],
                'expectedData'  => $this->getQuoteProductOffer(6, 44, 'kg', self::QPO_PRICE_TYPE1),
                'defaultData'   => $this->getQuoteProductOffer(6),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 11,
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 22,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(7, 11, 'kg', self::QPO_PRICE_TYPE1, $this->createPrice(22, 'EUR')),
                'defaultData'   => $this->getQuoteProductOffer(7),
            ],
        ];
    }

    /**
     * @param int $id
     * @param ProductUnit[] $productUnits
     * @param string $unitCode
     * @return \PHPUnit\Framework\MockObject\MockObject|QuoteProductOffer
     */
    protected function createQuoteProductOffer($id, array $productUnits = [], $unitCode = null)
    {
        $productUnit = null;

        $product = new Product();
        foreach ($productUnits as $unit) {
            $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

            if ($unitCode && $unit->getCode() === $unitCode) {
                $productUnit = $unit;
            }
        }

        /* @var $item \PHPUnit\Framework\MockObject\MockObject|QuoteProductOffer */
        $item = $this->createMock('Oro\Bundle\SaleBundle\Entity\QuoteProductOffer');
        $item
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id))
        ;
        $item
            ->expects($this->any())
            ->method('getQuoteProduct')
            ->will($this->returnValue((new QuoteProduct())->setProduct($product)))
        ;
        $item
            ->expects($this->any())
            ->method('getProductUnit')
            ->will($this->returnValue($productUnit))
        ;
        $item
            ->expects($this->any())
            ->method('getProductUnitCode')
            ->will($this->returnValue($unitCode))
        ;

        return $item;
    }

    /**
     * @param float $value
     * @param string $currency
     * @return Price
     */
    protected function createPrice($value, $currency)
    {
        return Price::create($value, $currency);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    QuoteProductOfferType::class => $this->formType,
                    ProductUnitSelectionType::class => new ProductUnitSelectionTypeStub(),
                    PriceType::class => $priceType,
                    CurrencySelectionType::class => $currencySelectionType,
                    ProductUnitSelectionType::class => $productUnitSelectionType,
                    QuantityType::class => $this->getQuantityType()
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testOnPreSetData()
    {
        /** @var $formMock FormInterface|\PHPUnit\Framework\MockObject\MockObject */
        $formMock = $this->createMock(FormInterface::class);
        $event = new FormEvent($formMock, new QuoteProductOffer());

        $formMock->expects($this->once())
            ->method('add')
            ->with(
                'price',
                PriceType::class,
                [
                    'currency_empty_value' => null,
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'oro.sale.quoteproductoffer.price.label',
                    //Price value may be not set by user while creating quote
                    'validation_groups' => [PriceType::OPTIONAL_VALIDATION_GROUP],
                    'match_price_on_null' => false
                ]
            );

        $this->formType->onPreSetData($event);
    }

    public function testOnPreSetDataNoEntity()
    {
        /** @var $formMock FormInterface|\PHPUnit\Framework\MockObject\MockObject */
        $formMock = $this->createMock(FormInterface::class);
        $event = new FormEvent($formMock, null);

        $formMock->expects($this->never())->method('add');

        $this->formType->onPreSetData($event);
    }
}
