<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Processor;

use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ProductBundle\ImportExport\Processor\ProductImportProcessor;
use Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy;

class ProductImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductImportProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new ProductImportProcessor();
    }

    public function testCloseWithClosableStrategy()
    {
        $strategy = $this->createMock(ProductStrategy::class);
        $strategy->expects($this->once())
            ->method('close');
        $this->processor->setStrategy($strategy);
        $this->processor->close();
    }

    public function testCloseWithNonClosableStrategy()
    {
        $strategy = $this->createMock(StrategyInterface::class);
        $strategy->expects($this->never())
            ->method($this->anything());
        $this->processor->setStrategy($strategy);
        $this->processor->close();
    }
}
