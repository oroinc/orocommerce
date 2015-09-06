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
     * @var string
     */
    protected $productClass = 'stdClass';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new ProductVariantLinksType($this->productClass, $this->getTransformerMock());
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

    /**
     * @return ProductVariantLinksDataTransformer|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getTransformerMock()
    {
        return $this->getMock('OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductVariantLinksDataTransformer');
    }
}
