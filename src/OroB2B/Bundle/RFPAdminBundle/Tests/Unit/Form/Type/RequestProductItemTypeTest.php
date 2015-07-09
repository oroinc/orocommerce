<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemType;

class RequestProductItemTypeTest extends AbstractTest
{
    /**
     * @var RequestProductItemType
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

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formType = new RequestProductItemType($this->translator);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductItemType::NAME, $this->formType->getName());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => 'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem',
                'intention'  => 'rfp_admin_request_product_item',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testPreSubmit()
    {
        $form = $this->factory->create($this->formType, null, []);

        $this->formType->preSubmit(new FormEvent($form, null));

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());
        $options = $config->getOptions();

        $this->assertFalse($options['disabled']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    /**
     * @param RequestProductItem $inputData
     * @param array $expectedData
     *
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData(RequestProductItem $inputData = null, array $expectedData = [])
    {
        $unitCode = $inputData ? $inputData->getProductUnitCode() : '';

        $this->translator
            ->expects($expectedData['empty_value'] ? $this->once() : $this->never())
            ->method('trans')
            ->with($expectedData['empty_value'], [
                '{title}' => $unitCode,
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
        $units = $this->getProductUnits(['kg', 'item']);

        return [
            'choices is []' => [
                'inputData'     => null,
                'expectedData'  => [
                    'choices'       => [],
                    'empty_value'   => null,
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'choices is ProductUnit[]' => [
                'inputData'     => $this->createRequestProductItem(1, $units, 'kg'),
                'expectedData'  => [
                    'choices'       => $units,
                    'empty_value'   => null,
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'choices is ProductUnit[] and unit is deleted' => [
                'inputData'     => $this->createRequestProductItem(1, $units, 'test'),
                'expectedData'  => [
                    'choices'       => $units,
                    'empty_value'   => 'orob2b.rfpadmin.message.requestproductitem.unit.removed',
                    'required'      => true,
                    'disabled'      => false,
                    'label'         => 'orob2b.product.productunit.entity_label',
                ],
            ],
            'choices is [] and unit is deleted' => [
                'inputData'     => $this->createRequestProductItem(1, [], 'test'),
                'expectedData'  => [
                    'choices'       => [],
                    'empty_value'   => 'orob2b.rfpadmin.message.requestproductitem.unit.removed',
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
                'expectedData'  => $this->getRequestProductItem(1),
                'defaultData'   => $this->getRequestProductItem(1),
            ],
            'empty quantity' => [
                'isValid'       => true,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, null, 'kg', $this->createPrice(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(3, 10, null, $this->createPrice(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(3),
            ],
            'empty price' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg'),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty price value' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price' => [
                        'currency' => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg'),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty price currency' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price' => [
                        'value' => 10,
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(10, null)),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty request product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(5, 10, 'kg', $this->createPrice(20, 'USD'))
                    ->setRequestProduct(null),
                'defaultData'   => $this->getRequestProductItem(5)
                    ->setRequestProduct(null),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(5, 10, 'kg', $this->createPrice(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(5),
            ],
        ];
    }

    /**
     * @param int $id
     * @param array $productUnits
     * @param string $unitCode
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestProductItem
     */
    protected function createRequestProductItem($id, array $productUnits = [], $unitCode = null)
    {
        $productUnit = null;

        $product = new Product();
        foreach ($productUnits as $unit) {
            $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

            if ($unitCode && $unit->getCode() == $unitCode) {
                $productUnit = $unit;
            }
        }

        /* @var $item \PHPUnit_Framework_MockObject_MockObject|RequestProductItem */
        $item = $this->getMock('OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem');
        $item
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id))
        ;
        $item
            ->expects($this->any())
            ->method('getRequestProduct')
            ->will($this->returnValue((new RequestProduct())->setProduct($product)))
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
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $optionalPriceType          = $this->prepareOptionalPriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    $priceType->getName()                   => $priceType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
