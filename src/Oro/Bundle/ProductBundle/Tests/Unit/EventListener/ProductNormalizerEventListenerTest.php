<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ProductNormalizerEventListener;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

class ProductNormalizerEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductNormalizerEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductNormalizerEventListener();
    }

    /**
     * @dataProvider normalizerDataProvider
     */
    public function testOnNormalize(array $plainData = [], array $context = [], array $expectedPlainData = [])
    {
        $event = new ProductNormalizerEvent(new Product(), $plainData, $context);

        $this->listener->onNormalize($event);

        $this->assertEquals($expectedPlainData, $event->getPlainData());
    }

    public function normalizerDataProvider(): array
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
     * @dataProvider denormalizerDataProvider
     */
    public function testOnDenormalize(array $plainData = [], array $context = [], array $expectedVariantFields = [])
    {
        $product = new Product();
        $event = new ProductNormalizerEvent($product, $plainData, $context);

        $this->listener->onDenormalize($event);

        $this->assertEquals($expectedVariantFields, $product->getVariantFields());
    }

    public function denormalizerDataProvider(): array
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
