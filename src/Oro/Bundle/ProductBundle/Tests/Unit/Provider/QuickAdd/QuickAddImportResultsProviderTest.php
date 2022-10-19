<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider\QuickAdd;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Provider\QuickAdd\QuickAddImportResultsProvider;
use Oro\Bundle\ProductBundle\Provider\QuickAdd\QuickAddImportResultsProviderInterface;

class QuickAddImportResultsProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetResultsWhenNoInnerProviders(): void
    {
        self::assertSame([], (new QuickAddImportResultsProvider([]))->getResults(new QuickAddRowCollection()));
    }

    public function testGetResults(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();

        $results1 = [
            'sku1' => ['sku' => 'sku1', 'product_name' => 'name1'],
        ];
        $provider1 = $this->createMock(QuickAddImportResultsProviderInterface::class);
        $provider1
            ->expects(self::once())
            ->method('getResults')
            ->with($quickAddRowCollection)
            ->willReturn($results1);

        $results2 = [
            'sku1' => ['product_name' => 'overridden name1'],
            'sku2' => ['sku' => 'sku2', 'product_name' => 'name2'],
        ];
        $provider2 = $this->createMock(QuickAddImportResultsProviderInterface::class);
        $provider2
            ->expects(self::once())
            ->method('getResults')
            ->with($quickAddRowCollection)
            ->willReturn($results2);
        $quickAddImportResultsProvider = new QuickAddImportResultsProvider([$provider1, $provider2]);

        self::assertSame(
            [
                ['sku' => 'sku1', 'product_name' => 'overridden name1'],
                ['sku' => 'sku2', 'product_name' => 'name2'],
            ],
            $quickAddImportResultsProvider->getResults($quickAddRowCollection)
        );
    }
}
