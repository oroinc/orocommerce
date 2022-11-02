<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductTypeType;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductTypeTypeTest extends FormIntegrationTestCase
{
    /** @var ProductTypeType */
    protected $productTypeType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductTypeProvider */
    protected $productTypeProvider;

    protected function setUp(): void
    {
        $this->productTypeProvider = new ProductTypeProvider();
        $this->productTypeType = new ProductTypeType($this->productTypeProvider);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([$this->productTypeType], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->productTypeType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create(ProductTypeType::class);
        $availableProductTypes = $this->productTypeProvider->getAvailableProductTypes();
        $choices = [];

        foreach ($availableProductTypes as $label => $value) {
            $choices[] = new ChoiceView($value, $value, $label);
        }

        $this->assertEquals(
            $choices,
            $form->createView()->vars['choices']
        );

        $this->assertEquals(
            Product::TYPE_SIMPLE,
            $form->getConfig()->getOptions()['preferred_choices']
        );
    }
}
