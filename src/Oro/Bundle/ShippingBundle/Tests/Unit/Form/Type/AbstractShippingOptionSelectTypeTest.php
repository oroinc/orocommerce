<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ShippingBundle\Form\Type\AbstractShippingOptionSelectType;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitProvider;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractShippingOptionSelectTypeTest extends FormIntegrationTestCase
{
    /** @var MeasureUnitProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $provider;

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
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

    public function testGetBlockPrefix()
    {
        $formType = new class() extends AbstractShippingOptionSelectType {
            const NAME = 'testname';
        };
        self::assertEquals('testname', $formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        self::assertEquals(EntityType::class, $this->formType->getParent());
    }

    public function testSetEntityClass()
    {
        $className = 'stdClass';

        // assertions
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::exactly(2))
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
        $resolver->expects(self::any())
            ->method('setAllowedTypes')
            ->willReturnSelf();

        $this->formType->configureOptions($resolver);
        $this->formType->setEntityClass($className);
        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $inputOptions,
        array $expectedOptions,
        mixed $submittedData,
        mixed $expectedData,
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

        $formatMap = [];
        for ($i = 0, $max = count($this->units); $i < $max; $i++) {
            $formatMap[] = [
                $this->units[$i],
                $formConfig->getOption('compact'),
                false,
                $expectedLabels[$i] ?? null
            ];
        }
        $this->formatter->expects($this->exactly(count($formatMap)))
            ->method('format')
            ->willReturnMap($formatMap);

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

    public function submitDataProvider(): array
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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
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

    protected function prepareChoices(): array
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $choices[$unitCode] = $this->createUnit($unitCode);
        }

        return $choices;
    }

    protected function createUnit(string $code): MeasureUnitInterface
    {
        $unit = $this->createMock(MeasureUnitInterface::class);
        $unit->expects(self::any())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }
}
