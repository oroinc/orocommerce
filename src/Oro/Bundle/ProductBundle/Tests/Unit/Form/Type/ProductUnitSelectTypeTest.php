<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Validation;

class ProductUnitSelectTypeTest extends FormIntegrationTestCase
{
    private UnitLabelFormatterInterface|MockObject $productUnitLabelFormatter;

    private ProductUnitSelectType $formType;

    protected function setUp(): void
    {
        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->formType = new ProductUnitSelectType($this->productUnitLabelFormatter);
        $this->formType->setEntityClass(ProductUnitPrecision::class);

        parent::setUp();
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
                    EntityType::class => new EntityTypeStub([
                        'item' => (new ProductUnit())->setCode('item'),
                        'kg' => (new ProductUnit())->setCode('kg')
                    ])
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, $submittedData, $expectedData): void
    {
        $form = $this->factory->create(ProductUnitSelectType::class, null, $inputOptions);

        self::assertNull($form->getData());

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            self::assertTrue($formConfig->hasOption($key));
            self::assertEquals($value, $formConfig->getOption($key));
        }

        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        /** @var ProductUnit $data */
        $data = $form->getData();

        self::assertEquals($expectedData, $data->getCode());
    }

    public function submitProvider(): array
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

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::exactly(2))
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

    public function testsFinishView(): void
    {
        $form = $this->factory->create(ProductUnitSelectType::class, null, ['compact' => false,]);
        self::assertNull($form->getData());

        $view = $form->createView();
        $this->productUnitLabelFormatter->expects(self::any())
            ->method('format')
            ->withConsecutive(['item', false], ['kg', false])
            ->willReturnOnConsecutiveCalls('oro.product_unit.item.label.full', 'oro.product_unit.kg.full');

        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());

        $labels = [];

        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            $labels[] = $choiceView->label;
        }

        self::assertEquals(['oro.product_unit.item.label.full', 'oro.product_unit.kg.full'], $labels);
    }
}
