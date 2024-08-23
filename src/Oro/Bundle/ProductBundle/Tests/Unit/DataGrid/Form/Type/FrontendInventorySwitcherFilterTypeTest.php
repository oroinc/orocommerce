<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Tests\Unit\Fixtures\CustomFormExtension;
use Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\AbstractTypeTestCase;
use Oro\Bundle\ProductBundle\DataGrid\Form\Type\FrontendInventorySwitcherFilterType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class FrontendInventorySwitcherFilterTypeTest extends AbstractTypeTestCase
{
    private FrontendInventorySwitcherFilterType $type;

    protected function setUp(): void
    {
        $translator = $this->createMockTranslator();

        $this->type = new FrontendInventorySwitcherFilterType($translator);

        $this->formExtensions[] = new CustomFormExtension([new FilterType($translator)]);
        $this->formExtensions[] = new PreloadedExtension([$this->type], []);

        parent::setUp();
    }

    protected function getTestFormType(): AbstractType
    {
        return $this->type;
    }

    public function configureOptionsDataProvider(): array
    {
        $choices = [
            'oro.product.frontend.product_inventory_filter.form.enabled.label' =>
                FrontendInventorySwitcherFilterType::TYPE_ENABLED,
        ];

        return [
            [
                'defaultOptions' => [
                    'field_type' => ChoiceType::class,
                    'field_options' => [
                        'choices' => $choices,
                        'multiple' => true,
                    ],
                    'operator_choices' => $choices,
                    'populate_default' => false,
                    'default_value' => null,
                    'null_value' => null,
                    'class' => null,
                ],
            ],
        ];
    }

    public function bindDataProvider(): array
    {
        return [
            'empty' => [
                'bindData' => [],
                'formData' => ['type' => null, 'value' => []],
                'viewData' => [
                    'value' => ['type' => null, 'value' => []],
                ],
            ],
            'predefined value choice' => [
                'bindData' => ['value' => 1],
                'formData' => ['type' => null, 'value' => 1],
                'viewData' => [
                    'value' => ['type' => null, 'value' => 1],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => ['On' => 1],
                    ],
                ],
            ],
            'invalid value choice' => [
                'bindData' => ['value' => 3],
                'formData' => ['type' => null],
                'viewData' => [
                    'value' => ['type' => null, 'value' => 3],
                ],
                'customOptions' => [
                    'field_options' => [
                        'choices' => ['One' => 1],
                    ],
                ],
            ],
        ];
    }
}
