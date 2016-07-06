<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\ImportExport\Normalizer;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\ImportExport\Normalizer\InventoryStatusNormalizer;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;

class InventoryStatusNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryStatusNormalizer
     */
    protected $inventoryStatusNormalizer;

    protected function setUp()
    {
        $this->inventoryStatusNormalizer = new InventoryStatusNormalizer();
    }

    public function testSupportsNormalization()
    {
        $data = '';
        $format = '';
        $this->assertFalse($this->inventoryStatusNormalizer->supportsNormalization($data, $format, []));

        $data = new Product();
        $this->assertFalse($this->inventoryStatusNormalizer->supportsNormalization($data, $format, []));

        $context = ['processorAlias' => InventoryStatusNormalizer::PRODUCT_INVENTORY_STATUS_ONLY_PROCESSOR];
        $this->assertTrue($this->inventoryStatusNormalizer->supportsNormalization($data, $format, $context));

        $context = ['processorAlias' => InventoryStatusNormalizer::WAREHOUSE_INVENTORY_STATUS_ONLY_PROCESSOR];
        $this->assertTrue($this->inventoryStatusNormalizer->supportsNormalization($data, $format, $context));
    }

    public function testNormalize()
    {
        $object = $this->getMock(StubProduct::class);
        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString('testName');
        $object->expects($this->once())
            ->method('getSku')
            ->willReturn('xxx');
        $object->expects($this->exactly(2))
            ->method('getDefaultName')
            ->willReturn($localizedFallbackValue);

        $inventoryStatus = $this->getMockBuilder(AbstractEnumValue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryStatus->expects($this->once())
            ->method('getName')
            ->willReturn('testStatus');
        $object->expects($this->exactly(2))
            ->method('getInventoryStatus')
            ->willReturn($inventoryStatus);

        $result = $this->inventoryStatusNormalizer->normalize($object, '', []);
        $this->assertEquals(
            ['product' => [
                'sku' => 'xxx',
                'defaultName' => 'testName',
                'inventoryStatus' => 'testStatus'
            ]],
            $result
        );
    }
}
