<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\FormBundle\Form\Extension\ConstraintAsOptionExtension;
use Oro\Bundle\FormBundle\Form\Extension\NumberTypeExtension;
use Oro\Bundle\FormBundle\Validator\ConstraintFactory;
use Oro\Bundle\LocaleBundle\Formatter\Factory\IntlNumberFormatterFactory;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Form\Type\Filter\ProductPriceFilterType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ProductPriceFilterTypeTest extends AbstractTypeTestCase
{
    private ProductPriceFilterType $type;
    private NumberFormatter $numberFormatter;
    private string $defaultLocale;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultLocale = \Locale::getDefault();

        $locale = 'en';
        \Locale::setDefault($locale);

        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects(self::any())
            ->method('getLocale')
            ->willReturn($locale);

        $this->numberFormatter = new NumberFormatter(
            $localeSettings,
            new IntlNumberFormatterFactory($localeSettings)
        );

        $translator = $this->createTranslator();

        $formatter = $this->createMock(UnitLabelFormatterInterface::class);
        $formatter->expects(self::any())
            ->method('format')
            ->with('item')
            ->willReturn('Item');

        $this->type = new ProductPriceFilterType($translator, $this->getDoctrine(), $formatter);

        $this->formExtensions[] = new CustomFormExtension([
            new FilterType($translator),
            new NumberFilterType($translator, $this->numberFormatter),
            new NumberRangeFilterType($translator)
        ]);
        $this->formExtensions[] = new PreloadedExtension(
            [$this->type],
            [NumberType::class => [new ConstraintAsOptionExtension(new ConstraintFactory())]]
        );

        parent::setUp();
    }

    #[\Override]
    protected function tearDown(): void
    {
        \Locale::setDefault($this->defaultLocale);
        parent::tearDown();
    }

    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new NumberTypeExtension($this->numberFormatter),
        ];
    }

    #[\Override]
    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    private function getDoctrine(): ManagerRegistry
    {
        $productUnitRepository = $this->createMock(ProductUnitRepository::class);
        $productUnitRepository->expects(self::any())
            ->method('getAllUnitCodes')
            ->willReturn(['item']);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->with(ProductUnit::class)
            ->willReturn($productUnitRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ProductUnit::class)
            ->willReturn($entityManager);

        return $doctrine;
    }

    #[\Override]
    public function configureOptionsDataProvider(): array
    {
        return [
            [
                'defaultOptions' => [
                    'data_type' => NumberRangeFilterType::DATA_DECIMAL,
                    'operator_choices' => [
                        'oro.filter.form.label_type_range_between' => NumberRangeFilterType::TYPE_BETWEEN,
                        'oro.filter.form.label_type_range_equals' => NumberRangeFilterType::TYPE_EQUAL,
                        'oro.filter.form.label_type_range_more_than' => NumberRangeFilterType::TYPE_GREATER_THAN,
                        'oro.filter.form.label_type_range_less_than' => NumberRangeFilterType::TYPE_LESS_THAN,
                        'oro.filter.form.label_type_range_more_equals' => NumberRangeFilterType::TYPE_GREATER_EQUAL,
                        'oro.filter.form.label_type_range_less_equals' => NumberRangeFilterType::TYPE_LESS_EQUAL,
                    ]
                ]
            ]
        ];
    }

    #[\Override]
    public function bindDataProvider(): array
    {
        return [
            'empty range' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'unit' => 'item'
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => null,
                    'value_end' => null,
                    'unit' => 'item'
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => null,
                        'value_end' => null,
                        'unit' => 'item'
                    ]
                ]
            ],
            'empty end value' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '1',
                    'unit' => 'item'
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '1',
                    'value_end' => null,
                    'unit' => 'item'
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => '1',
                        'value_end' => null,
                        'unit' => 'item'
                    ]
                ]
            ],
            'empty start value' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value_end' => '20',
                    'unit' => 'item'
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => null,
                    'value_end' => '20',
                    'unit' => 'item'
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => null,
                        'value_end' => '20',
                        'unit' => 'item'
                    ]
                ]
            ],
            'between range' => [
                'bindData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '10',
                    'value_end' => '20',
                    'unit' => 'item'
                ],
                'formData'      => [
                    'type' => NumberRangeFilterType::TYPE_BETWEEN,
                    'value' => '10',
                    'value_end' => '20',
                    'unit' => 'item'
                ],
                'viewData'      => [
                    'value' => [
                        'type' => NumberRangeFilterType::TYPE_BETWEEN,
                        'value' => '10',
                        'value_end' => '20',
                        'unit' => 'item'
                    ]
                ]
            ]
        ];
    }

    public function testGetParent(): void
    {
        self::assertEquals(NumberRangeFilterType::class, $this->type->getParent());
    }
}
