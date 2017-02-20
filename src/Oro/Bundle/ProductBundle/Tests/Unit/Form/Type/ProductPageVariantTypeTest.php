<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class ProductPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductPageVariantType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->type = new ProductPageVariantType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ProductSelectType::NAME => new EntityType([], ProductSelectType::NAME)
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, null);
        $this->assertTrue($form->has('productPageProduct'));
        $this->assertEquals(ProductPageContentVariantType::TYPE, $form->getConfig()->getOption('content_variant_type'));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getBlockPrefix());
    }
}
