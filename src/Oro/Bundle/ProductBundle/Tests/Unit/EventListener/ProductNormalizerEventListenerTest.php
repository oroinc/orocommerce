<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductNormalizerEventListener;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductNormalizerEventListener */
    protected $listener;

    /** @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $fieldHelper;

    protected function setUp()
    {
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductNormalizerEventListener($this->fieldHelper);
    }

    /**
     * @param array $plainData
     * @param array $context
     * @param array $expectedPlainData
     *
     * @dataProvider normalizerDataProvider
     */
    public function testOnNormalize(array $plainData = [], array $context = [], array $expectedPlainData = [])
    {
        $event = new ProductNormalizerEvent(new Product(), $plainData, $context);

        $this->listener->onNormalize($event);

        $this->assertEquals($expectedPlainData, $event->getPlainData());
    }

    /**
     * @return array
     */
    public function normalizerDataProvider()
    {
        return [
            'context not empty' => [['data' => 'val'], ['fieldName' => 'variantFields'], ['data' => 'val']],
            'missing data key' => [
                ['data' => 'val'],
                [],
                ['data' => 'val'],
            ],
            'normalizer fields invalid' => [
                ['data' => 'val', 'variantFields' => 'invalid'],
                [],
                ['data' => 'val', 'variantFields' => 'invalid'],
            ],
            'normalizer fields' => [
                ['data' => 'val', 'variantFields' => ['field1', 'field2']],
                [],
                ['data' => 'val', 'variantFields' => 'field1,field2'],
            ],
        ];
    }

    /**
     * @param array $plainData
     * @param array $context
     * @param array $expectedVariantFields
     *
     * @dataProvider denormalizerDataProvider
     */
    public function testOnDenormalize(array $plainData = [], array $context = [], array $expectedVariantFields = [])
    {
        $product = new Product();
        $event = new ProductNormalizerEvent($product, $plainData, $context);

        $this->listener->onDenormalize($event);

        $this->assertEquals($expectedVariantFields, $product->getVariantFields());
    }

    /**
     * @return array
     */
    public function denormalizerDataProvider()
    {
        return [
            'context not empty' => [['data' => 'val'], ['fieldName' => 'variantFields'], []],
            'missing data key' => [['data' => 'val'], [], []],
            'normalizer fields invalid' => [['data' => 'val', 'variantFields' => ['field1', 'field2']], [], [],],
            'normalizer fields' => [
                ['data' => 'val', 'variantFields' => 'field1,field2'],
                [],
                ['field1', 'field2'],
            ],
            'normalizer fields trim' => [
                ['data' => 'val', 'variantFields' => ', , field1 , field2 ,field3, '],
                [],
                ['field1', 'field2', 'field3'],
            ],
        ];
    }
}
