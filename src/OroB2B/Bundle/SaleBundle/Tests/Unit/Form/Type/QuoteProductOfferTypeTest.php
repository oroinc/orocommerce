<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;

class QuoteProductOfferTypeTest extends AbstractTest
{
    /**
     * @var QuoteProductOfferType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new QuoteProductOfferType($this->quoteProductOfferFormatter);
        $this->formType->setDataClass('OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer');
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
                'intention'     => 'sale_quote_product_offer',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_sale_quote_product_offer', $this->formType->getName());
    }

    /**
     * @param QuoteProductOffer $inputData
     * @param array $expectedData
     *
     * @dataProvider postSetDataProvider
     */
    public function testPostSetData(QuoteProductOffer $inputData, array $expectedData = [])
    {
        $form = $this->factory->create($this->formType, $inputData);

        $this->assertEquals($expectedData['priceType'], $form->get('priceType')->getData());
    }

    /**
     * @return array
     */
    public function postSetDataProvider()
    {
        return [
            'empty priceType' => [
                'input' => new QuoteProductOffer(),
                'expected' => [
                    'priceType' => QuoteProductOffer::PRICE_TYPE_UNIT,
                ],
            ],
            'existing priceType' => [
                'input' => (new QuoteProductOffer())->setPriceType(QuoteProductOffer::PRICE_TYPE_BUNDLED),
                'expected' => [
                    'priceType' => QuoteProductOffer::PRICE_TYPE_BUNDLED,
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
                'expectedData'  => $this->getQuoteProductOffer(1),
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
                'isValid'       => false,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'priceType'     => self::QPO_PRICE_TYPE1,
                    'price'         => [
                        'value'     => 11,
                        'currency'  => 'EUR',
                    ],
                ],
                'expectedData'  => $this
                    ->getQuoteProductOffer(3, null, 'kg', self::QPO_PRICE_TYPE1, $this->createPrice(11, 'EUR')),
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
                'isValid'       => false,
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
     * @return \PHPUnit_Framework_MockObject_MockObject|QuoteProductOffer
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

        /* @var $item \PHPUnit_Framework_MockObject_MockObject|QuoteProductOffer */
        $item = $this->getMock('OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer');
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
                    ProductUnitRemovedSelectionType::NAME   => new StubProductUnitRemovedSelectionType(),
                    $priceType->getName()                   => $priceType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
