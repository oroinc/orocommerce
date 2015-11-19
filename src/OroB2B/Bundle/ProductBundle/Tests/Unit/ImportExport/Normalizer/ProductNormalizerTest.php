<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

use OroB2B\Bundle\ProductBundle\ImportExport\Normalizer\ProductNormalizer;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductNormalizer
     */
    protected $productNormalizer;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var string
     */
    protected $productClass;

    protected function setUp()
    {
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productClass = 'OroB2B\Bundle\ProductBundle\Entity\Product';
        $this->productNormalizer = new ProductNormalizer($this->fieldHelper);
        $this->productNormalizer->setProductClass($this->productClass);
    }

    public function testNormalize()
    {
        $product = new Product();

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->willReturn('SKU-1');

        $result = $this->productNormalizer->normalize($product);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals($result['sku'], 'SKU-1');
    }

    public function testDenormalize()
    {
        $data = ['sku' => 'SKU-1'];

        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->fieldHelper->expects($this->once())
            ->method('setObjectValue')
            ->will(
                $this->returnCallback(
                    function ($result, $fieldName, $value) {
                        return $result->setSku($value);
                    }
                )
            );

        $result = $this->productNormalizer->denormalize($data, $this->productClass);
        $this->assertInstanceOf($this->productClass, $result);
        $this->assertEquals($result->getSku(), 'SKU-1');
    }

    public function testSupportsNormalization()
    {
        $product = new Product();
        $this->assertTrue($this->productNormalizer->supportsNormalization($product));
    }

    public function testSupportsDenormalization()
    {
        $product = new Product();
        $this->assertTrue($this->productNormalizer->supportsDenormalization($product, $this->productClass));
    }
}
