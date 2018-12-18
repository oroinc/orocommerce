<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class ProductUnitSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductUnitSelectType
     */
    private $formType;

    /**
     * @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productUnitLabelFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->formType = new ProductUnitSelectType($this->productUnitLabelFormatter);
        $this->formType->setEntityClass(ProductUnitPrecision::class);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityTypeStub(
            [
                'item' => (new ProductUnit())->setCode('item'),
                'kg' => (new ProductUnit())->setCode('kg')
            ]
        );

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EntityType::class => $entityType
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param $submittedData
     * @param $expectedData
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, $submittedData, $expectedData)
    {
        $form = $this->factory->create(ProductUnitSelectType::class, null, $inputOptions);

        $this->assertNull($form->getData());

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var ProductUnit $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data->getCode());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'without compact option' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false,
                ],
                'submittedData' => 'item',
                'expectedData' => 'item'
            ],
            'with compact option' => [
                'inputOptions' => [
                    'compact' => true,
                ],
                'expectedOptions' => [
                    'compact' => true,
                ],
                'submittedData' => 'kg',
                'expectedData' => 'kg'
            ]
        ];
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|OptionsResolver $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive(
                [
                    [
                        'product' => null,
                        'product_holder' => null,
                        'product_field' => 'product'
                    ]
                ],
                [
                    [
                        'class' => ProductUnitPrecision::class,
                        'choice_label' => 'code',
                        'compact' => false,
                        'choices_updated' => false,
                        'required' => true,
                    ]
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    public function testsFinishView()
    {
        $form = $this->factory->create(ProductUnitSelectType::class, null, ['compact' => false,]);
        $this->assertNull($form->getData());

        $view = $form->createView();
        $this->productUnitLabelFormatter->expects($this->any())
            ->method('format')
            ->withConsecutive(['item', false], ['kg', false])
            ->willReturnOnConsecutiveCalls('oro.product_unit.item.label.full', 'oro.product_unit.kg.full');

        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());

        $labels = [];

        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            $labels[] = $choiceView->label;
        }

        $this->assertEquals(['oro.product_unit.item.label.full', 'oro.product_unit.kg.full'], $labels);
    }
}
