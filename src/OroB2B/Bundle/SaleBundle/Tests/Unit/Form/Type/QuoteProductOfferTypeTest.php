<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class QuoteProductOfferTypeTest extends AbstractTest
{
    /**
     * @var QuoteProductOfferType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->formType = new QuoteProductOfferType($this->translator, $this->quoteProductOfferFormatter);
        $this->formType->setDataClass('OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer');
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
                'intention'     => 'sale_quote_product_offer',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_sale_quote_product_offer', $this->formType->getName());
    }

    public function testPreSubmit()
    {
        $form = $this->factory->create($this->formType, null, []);

        $this->formType->preSubmit(new FormEvent($form, null));

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());
        $options = $config->getOptions();

        $this->assertEquals(false, $options['disabled']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    /**
     * @param QuoteProductOffer $inputData
     * @param array $expectedData
     *
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData(QuoteProductOffer $inputData = null, array $expectedData = [])
    {
        $this->translator
            ->expects($expectedData['empty_value'] ? $this->once() : $this->never())
            ->method('trans')
            ->with($expectedData['empty_value'], [
                    '{title}' => $inputData ? $inputData->getProductUnitCode() : '',
            ])
            ->will($this->returnValue($expectedData['empty_value']))
        ;

        $form = $this->factory->create($this->formType);

        $this->formType->preSetData(new FormEvent($form, $inputData));

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());

        $options = $form->get('productUnit')->getConfig()->getOptions();

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $options[$key], $key);
        }
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        $units = $this->getProductUnits(['kg1', 'item1']);

        return [
            'choices is [] (empty)' => [
                'inputData'     => null,
                'expectedData'  => [
                    'choices'       => [],
                    'empty_value'   => null,
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'choices is ProductUnit[] (not empty)' => [
                'inputData'     => $this->createQuoteProductOffer(1, $units, 'item1'),
                'expectedData'  => [
                    'choices'       => $units,
                    'empty_value'   => null,
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'unit is deleted and choices is ProductUnit[]' => [
                'inputData'     => $this->createQuoteProductOffer(1, $units, 'test1'),
                'expectedData'  => [
                    'choices'       => $units,
                    'empty_value'   => 'orob2b.sale.quoteproduct.product.removed',
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'unit is deleted and choices is []' => [
                'inputData'     => $this->createQuoteProductOffer(1, [], 'test2'),
                'expectedData'  => [
                    'choices'       => [],
                    'empty_value'   => 'orob2b.sale.quoteproduct.product.removed',
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
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
