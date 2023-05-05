<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider\MultiShipping;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemGroupTitleProvider;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LineItemGroupTitleProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var LineItemGroupTitleProvider */
    private $titleProvider;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->titleProvider = new LineItemGroupTitleProvider(
            ['product.id' => 'product.sku'],
            $this->propertyAccessor,
            $this->entityNameResolver,
            $this->translator
        );
    }

    public function testGetTitle()
    {
        $lineItem = new CheckoutLineItem();
        $category = new Category();

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($lineItem, 'product.category')
            ->willReturn($category);

        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($category)
            ->willReturn('Test Category');

        $result = $this->titleProvider->getTitle('product.category:1', $lineItem);

        $this->assertEquals('Test Category', $result);
    }

    public function testGetTitleFromScalarValue()
    {
        $lineItem = new CheckoutLineItem();

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($lineItem, 'product.sku')
            ->willReturn('TEST-SKU');

        $this->entityNameResolver->expects($this->never())
            ->method('getName');

        $result = $this->titleProvider->getTitle('product.sku:TEST-SKU', $lineItem);

        $this->assertEquals('TEST-SKU', $result);
    }

    public function testGetTitleWithOtherItemsKey()
    {
        $lineItem = new CheckoutLineItem();

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $this->entityNameResolver->expects($this->never())
            ->method('getName');

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('Other Items');

        $result = $this->titleProvider->getTitle('other-items', $lineItem);

        $this->assertEquals('Other Items', $result);
    }

    public function testGetTitleWithMapping()
    {
        $lineItem = new CheckoutLineItem();

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($lineItem, 'product.sku')
            ->willReturn('TEST-SKU');

        $this->entityNameResolver->expects($this->never())
            ->method('getName');

        $result = $this->titleProvider->getTitle('product.id:25', $lineItem);

        $this->assertEquals('TEST-SKU', $result);
    }
}
