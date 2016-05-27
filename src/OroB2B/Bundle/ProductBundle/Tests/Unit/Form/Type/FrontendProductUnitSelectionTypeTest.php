<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitHolderTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FrontendProductUnitSelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FrontendProductUnitSelectionType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $units = ['test01', 'test02'];

    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($id, array $params) {
                    return isset($params['{title}']) ? $id . ':' . $params['{title}'] : $id;
                }
            );
        $productUnitLabelFormatter = new ProductUnitLabelFormatter($this->translator);
        $this->formType = new FrontendProductUnitSelectionType($productUnitLabelFormatter, $this->translator);
        $this->formType->setEntityClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->prepareChoices());
        $productUnitSelectionType =
            new ProductUnitSelectionTypeStub([1], FrontendProductUnitSelectionType::NAME);

        return [
            new PreloadedExtension(
                [
                    'entity' => $entityType,
                    $productUnitSelectionType->getName() => $productUnitSelectionType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testConfigureOptions()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver
         */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::exactly(2))
            ->method('setDefaults')
            ->with(static::isType('array'))
            ->willReturnOnConsecutiveCalls(
                [
                    'product' => null,
                    'product_holder' => null,
                    'product_field' => 'product',
                ],
                [
                    'class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                    'property' => 'code',
                    'compact' => false,
                    'choices_updated' => false,
                    'required' => true,
                    'empty_label' => 'orob2b.product.productunit.removed',
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param array $expectedLabels
     * @param string $submittedData
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, array $expectedLabels, $submittedData)
    {
        $form = $this->factory->create($this->formType, null, $inputOptions);

        $precision1 = new ProductUnitPrecision();
        $unit1 = new ProductUnit();
        $unit1->setCode('test01');
        $precision1->setUnit($unit1);
        $precision1->setSell(true);
        $precision2 = new ProductUnitPrecision();
        $unit2 = new ProductUnit();
        $unit2->setCode('test02');
        $precision2->setUnit($unit2);
        $precision2->setSell(true);

        $productUnitHolder = $this->createProductUnitHolder(
            1,
            'sku',
            $unit1,
            $this->createProductHolder(
                'sku',
                (new Product())->addUnitPrecision($precision1)->addUnitPrecision($precision2)
            )
        );

        $formParent = $this
            ->factory
            ->create(new ProductUnitHolderTypeStub(FrontendProductUnitSelectionType::NAME), $productUnitHolder);
        $form->setParent($formParent);
        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $view = $form->createView();
        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());
        $choices = $view->vars['choices'];

        foreach ($choices as $choice) {
            $label = array_shift($expectedLabels);
            $this->assertEquals($label, $choice->label);
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'without compact option' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                ],
                'expectedLabels' => [
                    'orob2b.product_unit.test01.label.full',
                    'orob2b.product_unit.test02.label.full',
                ],
                'submittedData' => 'test01',
            ],
            'with compact option' => [
                'inputOptions' => [
                    'compact' => true,
                ],
                'expectedOptions' => [
                    'compact' => true,
                ],
                'expectedLabels' => [
                    'orob2b.product_unit.test01.label.short',
                    'orob2b.product_unit.test02.label.short',
                ],
                'submittedData' => 'test02',
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(FrontendProductUnitSelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }

    /**
     * @dataProvider getProductUnitsDataProvider
     * @param array $product
     *
     **/
    public function testGetProductUnits($product)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
                     ->disableOriginalConstructor()
                     ->getMock();
        $formTypeClass = new \ReflectionClass('OroB2B\Bundle\ProductBundle\Form\Type\FrontendProductUnitSelectionType');
        $method = $formTypeClass->getMethod('getProductUnits');
        $method->setAccessible(true);
        $choices = $method->invoke($this->formType, $form, $product);
        $this->assertEquals(['item','set'], $choices);
    }

    /**
     * @return array
     */
    protected function prepareChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @param boolean $withParent
     *
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData = [], array $expectedData = [], $withParent = true)
    {
        $form = $this->factory->create($this->formType, null, $inputData['options']);

        if ($withParent) {
            $formParent = $this
                ->factory
                ->create(
                    new ProductUnitHolderTypeStub(FrontendProductUnitSelectionType::NAME),
                    $inputData['productUnitHolder']
                );
        } else {
            $formParent = null;
        }

        $form->setParent($formParent);

        $view = $form->createView();
        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());

        if (isset($view->vars['choices'])) {
            $choices = [];
            
            /**
             * @var ChoiceView $choice
             */
            foreach ($view->vars['choices'] as $choice) {
                $choices[$choice->value] = $choice->label;
            }
            $view->vars['choices'] = $choices;
        }

        foreach ($expectedData as $field => $value) {
            if (!isset($view->vars[$field])) {
                $view->vars[$field] = null;
            }
            static::assertEquals($value, $view->vars[$field]);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function finishViewProvider()
    {
        $precision = new ProductUnitPrecision();
        $unit = new ProductUnit();
        $unit->setCode('code');
        $precision->setUnit($unit);
        $precision->setSell(true);

        return [
            'without parent form' => [
                'inputData' => [
                    'options' => [],
                    'productHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder('sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => array_combine($this->units, $this->units),
                ],
                false,
            ],
            'without product holder' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => null,
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => array_combine(
                        $this->units,
                        ['orob2b.product_unit.test01.label.full', 'orob2b.product_unit.test02.label.full']
                    ),
                ],
            ],
            'filled item' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder('sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => array_combine(
                        $this->units,
                        ['orob2b.product_unit.test01.label.full', 'orob2b.product_unit.test02.label.full']
                    ),
                ],
            ],
            'existing product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'code',
                        $unit,
                        $this->createProductHolder('code', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'code' => 'orob2b.product_unit.code.label.full',
                    ],
                ],
            ],
            'existing product and compact mode' => [
                'inputData' => [
                    'options' => [
                        'compact' => true,
                    ],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'code',
                        $unit,
                        $this->createProductHolder('code', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'code' => 'orob2b.product_unit.code.label.short',
                    ],
                ],
            ],
            'new product unit holder' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        null,
                        null,
                        null,
                        $this->createProductHolder('sku', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'code' => 'orob2b.product_unit.code.label.full',
                    ],
                ],
            ],
            'deleted product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'code',
                        null,
                        $this->createProductHolder('code', null)
                    ),
                ],
                'expectedData' => [
                    'choices' => array_combine(
                        $this->units,
                        ['orob2b.product_unit.test01.label.full', 'orob2b.product_unit.test02.label.full']
                    ),
                ],
            ],
            'deleted product unit' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        (new ProductUnit())->setCode('sku'),
                        $this->createProductHolder('sku', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        null => 'orob2b.product.productunit.removed:sku',
                        'code' => 'orob2b.product_unit.code.label.full',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param $productUnitCode
     * @param ProductUnit $productUnit
     * @param ProductHolderInterface $productHolder
     * @return ProductUnitHolderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductUnitHolder(
        $id,
        $productUnitCode,
        ProductUnit $productUnit = null,
        ProductHolderInterface $productHolder = null
    ) {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitHolderInterface $productUnitHolder
         */
        $productUnitHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface');
        $productUnitHolder
            ->expects(static::any())
            ->method('getEntityIdentifier')
            ->willReturn($id);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductHolder')
            ->willReturn($productHolder);

        return $productUnitHolder;
    }

    /**
     * @param string $productSku
     * @param Product $product
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
     */
    protected function createProductHolder($productSku, Product $product = null)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface $productHolder
         */
        $productHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface');

        $productHolder
            ->expects(static::any())
            ->method('getProduct')
            ->willReturn($product);

        $productHolder
            ->expects(static::any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $productHolder;
    }

    /**
     * @dataProvider postSetDataProvider
     * @param mixed $productUnitHolder
     * @param mixed $productHolder
     * @param mixed $productUnit
     * @param array $options
     * @param bool $expectedFieldOverride
     */
    public function testPostSetData(
        $productUnitHolder,
        $productHolder,
        $productUnit,
        array $options = [],
        $expectedFieldOverride = false
    ) {
        $form = $this->factory->create($this->formType, $productUnitHolder, $options);

        /**
         * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $parentForm
         */
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm->expects($this->any())->method('has')->willReturn($expectedFieldOverride);

        /**
         * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $productForm
         */
        $productForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->setParent($parentForm);

        if ($expectedFieldOverride) {
            $productForm->expects($this->once())->method('getData')->willReturn($productHolder);
            $parentForm->expects($this->once())->method('get')->willReturn($productForm);
            $parentForm->expects($this->once())->method('add')->with(
                $this->isType('string'),
                $this->isType('string'),
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->callback(
                        function (array $options) use ($productUnit) {
                            $this->assertArrayHasKey('choices_updated', $options);
                            $this->assertTrue($options['choices_updated']);

                            $this->assertArrayHasKey('choices', $options);
                            $this->assertEquals([$productUnit], $options['choices']);

                            return true;
                        }
                    )
                )
            );
        } else {
            $parentForm->expects($this->never())->method('add');
        }

        $event = new FormEvent($form, null);
        $this->formType->setAcceptableUnits($event);
    }

    /**
     * @return array
     */
    public function postSetDataProvider()
    {
        return [
            'already updated' => [
                $this->getProductUnitHolder(),
                $this->getProductHolder(),
                $this->getProductUnit(),
                ['choices_updated' => true],
                false,
            ],
            'product found' => [
                $this->getProductUnitHolder(),
                $this->getProductHolder(),
                $this->getProductUnit(),
                [],
                true,
            ],
            'product not found' => [null, null, null, [], false,],
        ];
    }

    /**
     * @param string $code
     * @return ProductUnit
     */
    protected function getProductUnit($code = 'sku')
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    /**
     * @param string $code
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
     */
    protected function getProductHolder($code = 'sku')
    {
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($this->getProductUnit());
        $unitPrecision->setSell(true);
        $productHolder = $this->createProductHolder($code, (new Product())->addUnitPrecision($unitPrecision));

        return $productHolder;
    }

    /**
     * @param string $code
     * @return \PHPUnit_Framework_MockObject_MockObject|ProductUnitHolderInterface
     */
    protected function getProductUnitHolder($code = 'sku')
    {
        return $this->createProductUnitHolder(1, $code, $this->getProductUnit(), $this->getProductHolder());
    }

    /**
     * @dataProvider preSubmitDataProvider
     * @param mixed $product
     * @param mixed $data
     * @param string $expectedError
     */
    public function testPreSubmit($product, $data, $expectedError = '')
    {
        $form = $this->factory->create($this->formType, null, ['product' => $product]);

        $event = new FormEvent($form, $data);
        $this->formType->validateUnits($event);

        $this->assertEquals($expectedError, (string)$form->getErrors(true, true));
    }

    /**
     * @return array
     */
    public function preSubmitDataProvider()
    {
        $productUnit = new ProductUnit();
        $code = 'valid';
        $productUnit->setCode($code);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($productUnit);
        $unitPrecision->setSell(true);
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1);
        $product->addUnitPrecision($unitPrecision);

        return [
            'product not found' => [null, 'valid'],
            'product without units' => [
                $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1),
                'valid',
                'ERROR: orob2b.product.productunit.invalid' . "\n",
            ],
            'submit invalid' => [$product, 'not_valid', 'ERROR: orob2b.product.productunit.invalid' . "\n"],
            'submit valid' => [$product, 'valid'],
            'empty data' => [$product, null, 'ERROR: orob2b.product.productunit.invalid' . "\n"],
            'new product' => [new Product(), null],
        ];
    }

    /**
     * @param string $className
     * @param int $id
     *
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }

    /**
    * @return array
    */
    public function getProductUnitsDataProvider()
    {
        $product = new Product(1);
        $unitPrecisionsData = [['unit' => ['code' => 'item'], 'sell' => true],
            ['unit' => ['code' => 'set'], 'sell' => true],
            ['unit' => ['code' => 'kg'], 'sell' => false]];

        foreach ($unitPrecisionsData as $unitPrecisionData) {
            $unitPrecision = new ProductUnitPrecision();
            $productUnit = new ProductUnit();
            $productUnit->setCode($unitPrecisionData['unit']['code']);
            $unitPrecision->setUnit($productUnit);
            $unitPrecision->setSell($unitPrecisionData['sell']);
            $product->addUnitPrecision($unitPrecision);

        }
        return[['product'=> $product]];
    }
}
