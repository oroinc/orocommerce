<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ProductPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ProductSelectType::class => new EntityType([], ProductSelectType::NAME)
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductPageVariantType::class, null);
        $this->assertTrue($form->has('productPageProduct'));
        $this->assertEquals(ProductPageContentVariantType::TYPE, $form->getConfig()->getOption('content_variant_type'));
    }

    public function testGetName()
    {
        $type = new ProductPageVariantType();
        $this->assertEquals(ProductPageVariantType::NAME, $type->getName());
    }

    public function testGetBlockPrefix()
    {
        $type = new ProductPageVariantType();
        $this->assertEquals(ProductPageVariantType::NAME, $type->getBlockPrefix());
    }
}
