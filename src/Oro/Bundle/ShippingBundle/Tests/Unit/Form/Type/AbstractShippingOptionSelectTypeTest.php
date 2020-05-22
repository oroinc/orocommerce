<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ShippingBundle\Form\Type\AbstractShippingOptionSelectType;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitProvider;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractShippingOptionSelectTypeTest extends FormIntegrationTestCase
{
    /** @var MockObject|MeasureUnitProvider */
    protected $provider;

    /** @var UnitLabelFormatterInterface|MockObject */
    protected $formatter;

    /** @var AbstractShippingOptionSelectType */
    protected $formType;

    /** @var array */
    protected $units = ['lbs', 'kg', 'custom'];

    protected function configureProvider()
    {
        $this->provider = $this->createMock(MeasureUnitProvider::class);
    }

    protected function configureFormatter()
    {
        $this->formatter = $this->createMock(UnitLabelFormatterInterface::class);
    }

    protected function tearDown(): void
    {
        unset($this->formType, $this->formatter, $this->provider);

        parent::tearDown();
    }

    public function testGetBlockPrefix()
    {
        $formType = new class() extends AbstractShippingOptionSelectType {
            const NAME = 'testname';
        };
        static::assertEquals('testname', $formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        static::assertEquals(EntityType::class, $this->formType->getParent());
    }

    public function testSetEntityClass()
    {
        $className = 'stdClass';

        // assertions
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(static::exactly(2))
            ->method('setDefaults')
            ->withConsecutive(
                [
                    [
                        'class' => null, // empty
                        'choice_label' => 'code',
                        'compact' => false,
                        'full_list' => false,
                        'choices' => null,
                    ]
                ],
                [
                    [
                        'class' => $className,
                        'choice_label' => 'code',
                        'compact' => false,
                        'full_list' => false,
                        'choices' => null,
                    ]
                ]
            )
            ->willReturnSelf();
        $resolver->method('setAllowedTypes')->willReturnSelf();

        $this->formType->configureOptions($resolver);
        $this->formType->setEntityClass($className);
        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param array $expectedLabels
     * @param array $customChoices
     */
    public function testSubmit(
        array $inputOptions,
        array $expectedOptions,
        $submittedData,
        $expectedData,
        array $expectedLabels,
        array $customChoices = null
    ) {
        $units = ['lbs', 'kg'];

        $this->provider->expects($customChoices ? $this->never() : $this->once())
            ->method('getUnits')
            ->with(!$expectedOptions['full_list'])
            ->willReturn($units);

        if ($customChoices) {
            $inputOptions['choices'] = $customChoices;
            $units = $customChoices;
        }

        $form = $this->factory->create(get_class($this->formType), null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertTrue($formConfig->hasOption('choices'));
        $this->assertEquals($units, $formConfig->getOption('choices'));

        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->formatter->expects($this->at($key))
                ->method('format')
                ->with(array_shift($this->units), $formConfig->getOption('compact'), false)
                ->willReturn($expectedLabel);
        }

        $choices = $form->createView()->vars['choices'];
        foreach ($choices as $choice) {
            $this->assertEquals(array_shift($expectedLabels), $choice->label);
        }

        $this->assertNull($form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'inputOptions' => [],
                'expectedOptions' => ['compact' => false, 'full_list' => false, 'multiple' => false],
                'submittedData' => null,
                'expectedData' => null,
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
            ],
            [
                'inputOptions' => ['compact' => true, 'full_list' => true],
                'expectedOptions' => ['compact' => true, 'full_list' => true, 'multiple' => false],
                'submittedData' => 'lbs',
                'expectedData' => $this->createUnit('lbs'),
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
            ],
            [
                'inputOptions' => ['multiple' => true],
                'expectedOptions' => ['compact' => false, 'full_list' => false, 'multiple' => true],
                'submittedData' => ['lbs', 'kg'],
                'expectedData' => [$this->createUnit('lbs'), $this->createUnit('kg')],
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
            ],
            [
                'inputOptions' => [],
                'expectedOptions' => ['compact' => false, 'full_list' => false],
                'submittedData' => 'custom',
                'expectedData' => $this->createUnit('custom'),
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
                'choices' => ['custom']
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EntityType::class => new EntityTypeStub($this->prepareChoices())
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @return array
     */
    protected function prepareChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $choices[$unitCode] = $this->createUnit($unitCode);
        }

        return $choices;
    }

    /**
     * @param string $code
     * @return MeasureUnitInterface|MockObject
     */
    protected function createUnit($code)
    {
        $unit = $this->createMock(MeasureUnitInterface::class);
        $unit->expects(static::any())->method('getCode')->willReturn($code);

        return $unit;
    }
}
