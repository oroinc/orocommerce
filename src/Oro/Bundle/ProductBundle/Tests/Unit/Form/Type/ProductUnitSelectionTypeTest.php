<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitHolderTypeStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductUnitSelectionTypeTest extends FormIntegrationTestCase
{
    private array $units = ['test01', 'test02'];

    private ProductUnitSelectionType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $params) {
                return isset($params['{title}']) ? $id . ':' . $params['{title}'] : $id;
            });

        $productUnitLabelFormatter = new UnitLabelFormatter($translator);
        $productUnitLabelFormatter->setTranslationPrefix('oro.product_unit');

        $this->formType = new ProductUnitSelectionType($productUnitLabelFormatter, $translator);
        $this->formType->setEntityClass(ProductUnit::class);

        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EntityType::class => new EntityTypeStub($this->prepareChoices()),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver
            ->expects(self::exactly(2))
            ->method('setDefaults')
            ->withConsecutive(
                [
                    [
                        'product' => null,
                        'product_holder' => null,
                        'product_field' => 'product',
                    ],
                ],
                [
                    [
                        'class' => ProductUnit::class,
                        'choice_label' => 'code',
                        'compact' => false,
                        'choices_updated' => false,
                        'required' => true,
                        'empty_label' => 'oro.product.productunit.removed',
                        'sell' => null,
                        'init_choices' => false,
                    ],
                ]
            );

        $resolver
            ->expects(self::exactly(4))
            ->method('setAllowedTypes')
            ->withConsecutive(
                ['product', [Product::class, 'null']],
                ['product_holder', [ProductHolderInterface::class, 'null']],
                ['product_field', 'string'],
                ['init_choices', 'boolean'],
            );

        $resolver
            ->expects(self::once())
            ->method('setInfo')
            ->with('init_choices', 'Initialize "choices" option based on the "product" option.')
            ->willReturn($resolver);

        $resolver
            ->expects(self::once())
            ->method('setDefault')
            ->with('choices', self::isInstanceOf(\Closure::class))
            ->willReturn($resolver);

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider getProductUnitsDataProvider
     */
    public function testGetProductUnits(
        array $option,
        ProductUnitPrecision $primaryUnitPrecision,
        ArrayCollection $additionalUnitPrecisions,
        array $expectedData
    ): void {
        $config = $this->createMock(FormConfigInterface::class);
        $config
            ->expects(self::any())
            ->method('getOptions')
            ->willReturn($option);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects(self::any())
            ->method('getConfig')
            ->willReturn($config);

        $product = $this->createMock(Product::class);
        $product
            ->expects(self::any())
            ->method('getPrimaryUnitPrecision')
            ->willReturn($primaryUnitPrecision);

        $product
            ->expects(self::any())
            ->method('getAdditionalUnitPrecisions')
            ->willReturn($additionalUnitPrecisions);

        $result = ReflectionUtil::callMethod($this->formType, 'getProductUnits', [$form, $product]);

        self::assertCount(count($expectedData), $result);
        foreach ($result as $index => $unit) {
            self::assertEquals($expectedData[$index], $unit->getCode());
        }
    }

    private function makePrecision(string $code, bool $sell): ProductUnitPrecision
    {
        $unit = new ProductUnit();
        $unit->setCode($code);
        $precision = new ProductUnitPrecision();
        $precision->setUnit($unit);
        $precision->setSell($sell);
        return $precision;
    }

    public function getProductUnitsDataProvider(): array
    {
        $primaryUnitPrecision = $this->makePrecision('box', true);

        $precision1 = $this->makePrecision('set', true);
        $precision2 = $this->makePrecision('each', false);
        $additionalUnitPrecisions = new ArrayCollection();
        $additionalUnitPrecisions->add($precision1);
        $additionalUnitPrecisions->add($precision2);

        return [
            'with_sell_true' => [
                ['sell' => true],
                $primaryUnitPrecision,
                $additionalUnitPrecisions,
                ['box', 'set'],
            ],
            'with_sell_null' => [
                ['sell' => null],
                $primaryUnitPrecision,
                $additionalUnitPrecisions,
                ['box', 'set', 'each'],
            ],
            'without_additional' => [
                ['sell' => null],
                $primaryUnitPrecision,
                new ArrayCollection(),
                ['box'],
            ],
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $inputOptions,
        array $expectedOptions,
        array $expectedLabels,
        string $submittedData
    ): void {
        $form = $this->factory->create(ProductUnitSelectionType::class, null, $inputOptions);

        $precision1 = new ProductUnitPrecision();
        $unit1 = new ProductUnit();
        $unit1->setCode('test01');
        $precision1->setUnit($unit1);
        $precision2 = new ProductUnitPrecision();
        $unit2 = new ProductUnit();
        $unit2->setCode('test02');
        $precision2->setUnit($unit2);

        $productUnitHolder = $this->createProductUnitHolder(
            1,
            'sku',
            $unit1,
            $this->createProductHolder(
                'sku',
                (new Product())
                    ->addUnitPrecision($precision1)
                    ->addUnitPrecision($precision2)
            )
        );

        $formParent = $this->factory->create(ProductUnitHolderTypeStub::class, $productUnitHolder);
        $form->setParent($formParent);
        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            self::assertTrue($formConfig->hasOption($key));
            self::assertEquals($value, $formConfig->getOption($key));
        }

        $view = $form->createView();
        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());
        $choices = $view->vars['choices'];

        foreach ($choices as $choice) {
            $label = array_shift($expectedLabels);
            self::assertEquals($label, $choice->label);
        }

        self::assertNull($form->getData());
        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($submittedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'without compact option' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                ],
                'expectedLabels' => [
                    'oro.product_unit.test01.label.full',
                    'oro.product_unit.test02.label.full',
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
                    'oro.product_unit.test01.label.short',
                    'oro.product_unit.test02.label.short',
                ],
                'submittedData' => 'test02',
            ],
        ];
    }

    public function testSubmitWithProductOption(): void
    {
        $expectedChoices = $this->units;
        $expectedLabels = [
            'oro.product_unit.' . $this->units[0] . '.label.full',
            'oro.product_unit.' . $this->units[1] . '.label.full',
        ];
        $unit1 = new ProductUnit();
        $unit1->setCode($this->units[0]);
        $precision1 = new ProductUnitPrecision();
        $precision1->setUnit($unit1);
        $precision1->setPrecision(0);

        $unit2 = new ProductUnit();
        $unit2->setCode($this->units[1]);
        $precision2 = new ProductUnitPrecision();
        $precision2->setUnit($unit2);
        $precision2->setPrecision(2);

        $product = new Product();
        $product->setPrimaryUnitPrecision($precision1);
        $product->addAdditionalUnitPrecision($precision2);

        $form = $this->factory->create(ProductUnitSelectionType::class, null, [
            'product' => $product,
        ]);

        $formConfig = $form->getConfig();
        self::assertTrue($formConfig->hasOption('product'));
        self::assertSame($product, $formConfig->getOption('product'));

        $view = $form->createView();
        $productUnitHolder = $this->createProductUnitHolder(
            1,
            'item',
            new ProductUnit(),
            $this->createProductHolder('sku', null)
        );
        $formParent = $this->factory->create(ProductUnitHolderTypeStub::class, $productUnitHolder);
        $form->setParent($formParent);

        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());
        $choices = $view->vars['choices'];

        self::assertCount(count($expectedChoices), $choices);

        foreach ($choices as $index => $choice) {
            self::assertEquals($expectedChoices[$index], $choice->value);
            self::assertEquals($expectedLabels[$index], $choice->label);
        }

        self::assertNull($form->getData());
        $form->submit($this->units[0]);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals((new ProductUnit())->setCode($this->units[0]), $form->getData());
    }

    public function testGetParent(): void
    {
        self::assertEquals(EntityType::class, $this->formType->getParent());
    }

    private function prepareChoices(): array
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
     * @dataProvider finishViewProvider
     */
    public function testFinishView(
        array $inputData = [],
        array $expectedData = [],
        bool $withParent = true
    ): void {
        $form = $this->factory->create(ProductUnitSelectionType::class, null, $inputData['options']);

        if ($withParent) {
            $formParent = $this->factory->create(
                ProductUnitHolderTypeStub::class,
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
            /* @var ChoiceView $choice */
            foreach ($view->vars['choices'] as $choice) {
                $choices[$choice->value] = $choice->label;
            }
            $view->vars['choices'] = $choices;
        }

        foreach ($expectedData as $field => $value) {
            if (!isset($view->vars[$field])) {
                $view->vars[$field] = null;
            }
            self::assertEquals($value, $view->vars[$field]);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function finishViewProvider(): array
    {
        $precision = new ProductUnitPrecision();
        $unit = new ProductUnit();
        $unit->setCode('each');
        $precision->setUnit($unit);

        return [
            'without parent form' => [
                'inputData' => [
                    'options' => [],
                    'productHolder' => $this->createProductUnitHolder(
                        1,
                        'item',
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
                        ['oro.product_unit.test01.label.full', 'oro.product_unit.test02.label.full']
                    ),
                ],
            ],
            'filled item' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'item',
                        new ProductUnit(),
                        $this->createProductHolder('sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => array_combine(
                        $this->units,
                        ['oro.product_unit.test01.label.full', 'oro.product_unit.test02.label.full']
                    ),
                ],
            ],
            'existing product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'each',
                        $unit,
                        $this->createProductHolder('each', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'each' => 'oro.product_unit.each.label.full',
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
                        'each',
                        $unit,
                        $this->createProductHolder('each', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'each' => 'oro.product_unit.each.label.short',
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
                        'each' => 'oro.product_unit.each.label.full',
                    ],
                ],
            ],
            'deleted product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'item',
                        null,
                        $this->createProductHolder('item', null)
                    ),
                ],
                'expectedData' => [
                    'choices' => array_combine(
                        $this->units,
                        ['oro.product_unit.test01.label.full', 'oro.product_unit.test02.label.full']
                    ),
                ],
            ],
            'deleted product unit' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'item',
                        (new ProductUnit())->setCode('item'),
                        $this->createProductHolder('item', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'item' => 'oro.product.productunit.removed:item',
                        'each' => 'oro.product_unit.each.label.full',
                    ],
                ],
            ],
            'missing product unit and product unit code with entity identifier' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        '',
                        null,
                        $this->createProductHolder('sku', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'choices' => [
                        'each' => 'oro.product_unit.each.label.full',
                    ],
                ],
            ],
        ];
    }

    private function createProductUnitHolder(
        ?int $id,
        ?string $productUnitCode,
        ?ProductUnit $productUnit = null,
        ?ProductHolderInterface $productHolder = null
    ): ProductUnitHolderInterface {
        $productUnitHolder = $this->createMock(ProductUnitHolderInterface::class);
        $productUnitHolder
            ->expects(self::any())
            ->method('getEntityIdentifier')
            ->willReturn($id);

        $productUnitHolder
            ->expects(self::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);

        $productUnitHolder
            ->expects(self::any())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);

        $productUnitHolder
            ->expects(self::any())
            ->method('getProductHolder')
            ->willReturn($productHolder);

        return $productUnitHolder;
    }

    private function createProductHolder(
        string $productSku,
        ?Product $product = null
    ): ProductHolderInterface {
        $productHolder = $this->createMock(ProductHolderInterface::class);
        $productHolder
            ->expects(self::any())
            ->method('getProduct')
            ->willReturn($product);

        $productHolder
            ->expects(self::any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $productHolder;
    }

    /**
     * @dataProvider postSetDataProvider
     */
    public function testPostSetData(
        mixed $productUnitHolder,
        mixed $productHolder,
        mixed $productUnit,
        array $options = [],
        bool $expectedFieldOverride = false
    ): void {
        $form = $this->factory->create(ProductUnitSelectionType::class, $productUnitHolder, $options);

        $parentForm = $this->createMock(FormInterface::class);
        $parentForm
            ->expects(self::any())
            ->method('has')
            ->with('product')
            ->willReturn($expectedFieldOverride);

        $productForm = $this->createMock(FormInterface::class);
        $form->setParent($parentForm);

        if ($expectedFieldOverride) {
            $productForm
                ->expects(self::once())
                ->method('getData')
                ->willReturn($productHolder);

            $parentForm
                ->expects(self::once())
                ->method('get')
                ->with('product')
                ->willReturn($productForm);

            $parentForm
                ->expects(self::once())
                ->method('add')
                ->with(
                    self::isType('string'),
                    self::isType('string'),
                    self::callback(function (array $options) use ($productUnit) {
                        self::assertArrayHasKey('choices_updated', $options);
                        self::assertTrue($options['choices_updated']);

                        self::assertArrayHasKey('choices', $options);
                        self::assertEquals([$productUnit->getCode() => 0], $options['choices']);

                        return true;
                    })
                );
        } else {
            $parentForm
                ->expects(self::never())
                ->method('add');
        }

        $event = new FormEvent($form, null);
        $this->formType->setAcceptableUnits($event);
    }

    public function postSetDataProvider(): array
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

    private function getProductUnit(string $code = 'sku'): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function getProductHolder(string $code = 'sku'): ProductHolderInterface
    {
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($this->getProductUnit());

        return $this->createProductHolder($code, (new Product())->addUnitPrecision($unitPrecision));
    }

    private function getProductUnitHolder(string $code = 'sku'): ProductUnitHolderInterface
    {
        return $this->createProductUnitHolder(1, $code, $this->getProductUnit(), $this->getProductHolder());
    }

    /**
     * @dataProvider preSubmitDataProvider
     */
    public function testPreSubmit(mixed $product, mixed $data, string $expectedError = ''): void
    {
        $form = $this->factory->create(ProductUnitSelectionType::class, null, ['product' => $product]);

        $event = new FormEvent($form, $data);
        $this->formType->validateUnits($event);

        self::assertEquals($expectedError, (string)$form->getErrors(true, true));
    }

    public function preSubmitDataProvider(): array
    {
        $productUnit = new ProductUnit();
        $code = 'valid';
        $productUnit->setCode($code);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($productUnit);
        $product = new Product();
        ReflectionUtil::setId($product, 1);
        $product->addUnitPrecision($unitPrecision);
        $productNoUnits = new Product();
        ReflectionUtil::setId($productNoUnits, 1);

        return [
            'product not found' => [null, 'valid'],
            'product without units' => [
                $productNoUnits,
                'valid',
                'ERROR: oro.product.productunit.invalid' . "\n",
            ],
            'submit invalid' => [
                $product,
                'not_valid',
                'ERROR: oro.product.productunit.invalid' . "\n",
            ],
            'submit valid' => [$product, 'valid'],
            'empty data' => [
                $product,
                null,
                'ERROR: oro.product.productunit.invalid' . "\n",
            ],
            'new product' => [new Product(), null],
        ];
    }
}
