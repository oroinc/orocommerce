<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization;
use Oro\Bundle\ProductBundle\Api\Processor\ReindexProductFallbackValueChanged;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\SearchBundle\Utils\IndexationEntitiesContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexProductFallbackValueChangedTest extends TestCase
{
    private IndexationEntitiesContainer|MockObject $entitiesContainer;

    private ReindexProductFallbackValueChanged $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->entitiesContainer = $this->createMock(IndexationEntitiesContainer::class);
        $this->processor = new ReindexProductFallbackValueChanged($this->entitiesContainer);
    }

    public function testProcess(): void
    {
        $product = $this->createMock(Product::class);
        $product
            ->expects($this->once())
            ->method('getDenormalizedDefaultName')
            ->willReturn('Old Name');
        $product
            ->expects($this->once())
            ->method('updateDenormalizedProperties');
        $product
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $productName = $this->createMock(ProductName::class);
        $productName
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $productName
            ->expects($this->once())
            ->method('getLocalization')
            ->willReturn(null);
        $productName
            ->expects($this->once())
            ->method('getString')
            ->willReturn('New Name');

        $context = $this->createMock(CustomizeFormDataContext::class);
        $context
            ->expects($this->once())
            ->method('getData')
            ->willReturn($productName);

        $this->entitiesContainer->expects($this->once())
            ->method('addEntity')
            ->with($product);

        $this->processor->process($context);
    }

    public function testProcessNotDefaultName(): void
    {
        $localization = new Localization();

        $product = $this->createMock(Product::class);
        $product
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $product
            ->expects($this->never())
            ->method('updateDenormalizedProperties');

        $productName = $this->createMock(ProductName::class);
        $productName
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $productName
            ->expects($this->once())
            ->method('getLocalization')
            ->willReturn($localization);

        $context = $this->createMock(CustomizeFormDataContext::class);
        $context
            ->expects($this->once())
            ->method('getData')
            ->willReturn($productName);

        $this->entitiesContainer
            ->expects($this->once())
            ->method('addEntity')
            ->with($product);

        $this->processor->process($context);
    }
}
