<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Configuration;

use Oro\Bundle\ProductBundle\Form\Configuration\ProductPageTemplateBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductPageTemplateBuilderTest extends TestCase
{
    private FormBuilderInterface $formBuilder;

    private ProductPageTemplateBuilder $productPageTemplateBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->productPageTemplateBuilder = new ProductPageTemplateBuilder(new Packages());
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(string $type, bool $expectedResult): void
    {
        self::assertEquals(
            $expectedResult,
            $this->productPageTemplateBuilder->supports(['type' => $type])
        );
    }

    public function getSupportsDataProvider(): array
    {
        return [
            ['unknown_type', false],
            [ProductPageTemplateBuilder::getType(), true],
        ];
    }

    /**
     * @dataProvider optionDataProvider
     */
    public function testThatOptionBuiltCorrectly(array $option, array $expected): void
    {
        $this->formBuilder
            ->expects(self::once())
            ->method('add')
            ->with(
                $expected['name'],
                $expected['form_type'],
                $expected['options']
            );

        $this->productPageTemplateBuilder->buildOption($this->formBuilder, $option);
    }

    private function optionDataProvider(): array
    {
        $placeholder = 'oro.product.theme.default.configuration.product_details.template.values.default';

        return [
            'with previews' => [
                [
                    'name' => 'general-product-page-template',
                    'label' => 'Product Page Template',
                    'type' => ProductPageTemplateBuilder::getType(),
                    'default' => null,
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ],
                    'previews' => [
                        'option_1' => 'path/to/previews/option_1.png',
                        'option_2' => 'path/to/previews/option_2.png',
                    ],
                ],
                [
                    'name' => 'general-product-page-template',
                    'form_type' => ChoiceType::class,
                    'options' => [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'placeholder' => $placeholder,
                        'label' => 'Product Page Template',
                        'attr' => [
                            'data-role' => 'change-preview',
                            'data-preview-key' => 'general-product-page-template',
                        ],
                        'choices' => [
                            'Option 1' => 'option_1',
                            'Option 2' => 'option_2',
                        ],
                        'choice_attr' => function () {
                        }
                    ],
                ]
            ],
            'no previews' => [
                [
                    'name' => 'general-product-page-template',
                    'label' => 'Product Page Template',
                    'type' => ProductPageTemplateBuilder::getType(),
                    'values' => [
                        'option_1' => 'Option 1',
                        'option_2' => 'Option 2'
                    ],
                ],
                [
                    'name' => 'general-product-page-template',
                    'form_type' => ChoiceType::class,
                    'options' => [
                        'required' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'placeholder' => $placeholder,
                        'label' => 'Product Page Template',
                        'attr' => [],
                        'choices' => [
                            'Option 1' => 'option_1',
                            'Option 2' => 'option_2',
                        ],
                        'choice_attr' => function () {
                        }
                    ],
                ]
            ]
        ];
    }
}
