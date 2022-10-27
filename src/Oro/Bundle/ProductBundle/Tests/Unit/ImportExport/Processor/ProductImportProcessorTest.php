<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Processor;

use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ProductBundle\ImportExport\Processor\ProductImportProcessor;
use Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy;

class ProductImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductImportProcessor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->processor = new ProductImportProcessor();
    }

    public function testCloseWithClosableStrategy()
    {
        /** @var ProductStrategy|\PHPUnit\Framework\MockObject\MockObject $strategy */
        $strategy = $this->getMockBuilder(ProductStrategy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $strategy->expects($this->once())
            ->method('close');
        $this->processor->setStrategy($strategy);
        $this->processor->close();
    }

    public function testCloseWithNonClosableStrategy()
    {
        /** @var StrategyInterface|\PHPUnit\Framework\MockObject\MockObject $strategy */
        $strategy = $this->createMock(StrategyInterface::class);
        $strategy->expects($this->never())
            ->method($this->anything());
        $this->processor->setStrategy($strategy);
        $this->processor->close();
    }
}
