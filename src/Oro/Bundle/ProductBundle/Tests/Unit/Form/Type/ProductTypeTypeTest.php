<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductTypeType;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductTypeTypeTest extends FormIntegrationTestCase
{
    private ProductTypeProvider|MockObject $productTypeProvider;

    private ProductTypeType $productTypeType;

    protected function setUp(): void
    {
        $this->productTypeProvider = new ProductTypeProvider(Product::getTypes());
        $this->productTypeType = new ProductTypeType($this->productTypeProvider);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->productTypeType], [])
        ];
    }

    public function testGetParent(): void
    {
        self::assertEquals(ChoiceType::class, $this->productTypeType->getParent());
    }

    public function testChoices(): void
    {
        $form = $this->factory->create(ProductTypeType::class);

        $this->assertEquals(
            [0 => new ChoiceView(Product::TYPE_SIMPLE, Product::TYPE_SIMPLE, 'oro.product.type.simple')],
            $form->createView()->vars['preferred_choices']
        );

        self::assertEquals(
            [
                1 => new ChoiceView(
                    Product::TYPE_CONFIGURABLE,
                    Product::TYPE_CONFIGURABLE,
                    'oro.product.type.configurable'
                ),
                2 => new ChoiceView(Product::TYPE_KIT, Product::TYPE_KIT, 'oro.product.type.kit'),
            ],
            $form->createView()->vars['choices']
        );

        $this->assertFalse(
            $form->getConfig()->getOptions()['duplicate_preferred_choices']
        );

        self::assertEquals(
            [Product::TYPE_SIMPLE],
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}
