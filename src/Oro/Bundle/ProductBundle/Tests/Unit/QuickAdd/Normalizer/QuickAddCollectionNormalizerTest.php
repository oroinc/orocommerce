<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\QuickAdd\Normalizer;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizer;
use Oro\Bundle\ProductBundle\QuickAdd\Normalizer\QuickAddCollectionNormalizerInterface;

class QuickAddCollectionNormalizerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetResultsWhenNoInnerProviders(): void
    {
        self::assertSame(
            ['errors' => [], 'items' => []],
            (new QuickAddCollectionNormalizer([]))->normalize(new QuickAddRowCollection())
        );
    }

    public function testGetResults(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();

        $results1 = [
            'errors' => [],
            'items' => [1 => ['index' => 1, 'sku' => 'sku1', 'product_name' => 'name1']],
        ];
        $provider1 = $this->createMock(QuickAddCollectionNormalizerInterface::class);
        $provider1
            ->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($results1);

        $results2 = [
            'errors' => [['message' => 'sample error', 'propertyPath' => '']],
            'items' => [
                1 => ['index' => 1, 'product_name' => 'overridden name1'],
                2 => ['index' => 2, 'sku' => 'sku2', 'product_name' => 'name2'],
            ],
        ];
        $provider2 = $this->createMock(QuickAddCollectionNormalizerInterface::class);
        $provider2
            ->expects(self::once())
            ->method('normalize')
            ->with($quickAddRowCollection)
            ->willReturn($results2);
        $quickAddCollectionNormalizer = new QuickAddCollectionNormalizer([$provider1, $provider2]);

        self::assertSame(
            [
                'errors' => [['message' => 'sample error', 'propertyPath' => '']],
                'items' => [
                    ['index' => 1, 'sku' => 'sku1', 'product_name' => 'overridden name1'],
                    ['index' => 2, 'sku' => 'sku2', 'product_name' => 'name2'],
                ],
            ],
            $quickAddCollectionNormalizer->normalize($quickAddRowCollection)
        );
    }
}
