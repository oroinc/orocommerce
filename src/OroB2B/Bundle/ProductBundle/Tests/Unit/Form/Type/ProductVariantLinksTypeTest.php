<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductVariantLinksType;

class ProductVariantLinksTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductVariantLinksType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ProductVariantLinksType();
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(ProductVariantLinksType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([
                EntityIdentifierType::NAME => new StubEntityIdentifierType([]),
            ], [])
        ];
    }
}
