<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Model\ProductView;

class ProductViewTest extends \PHPUnit\Framework\TestCase
{
    public function testNotSetAttributeExistence(): void
    {
        $productView = new ProductView();

        self::assertFalse($productView->has('attr1'));
        self::assertFalse(isset($productView->attr1));
    }

    public function testNotSetAttributeGetting(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "attr1" attribute does not exist.');

        $productView = new ProductView();

        $productView->get('attr1');
    }

    public function testNotSetAttributeGettingViaMagicMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "attr1" attribute does not exist.');

        $productView = new ProductView();

        $productView->attr1;
    }

    public function testIdAttribute(): void
    {
        $productView = new ProductView();

        $value = 123;
        $productView->set('id', $value);

        self::assertTrue($productView->has('id'));
        self::assertTrue(isset($productView->id));

        self::assertSame($value, $productView->get('id'));
        self::assertSame($value, $productView->id);
        self::assertSame($value, $productView->getId());
    }

    public function testAnotherAttribute(): void
    {
        $productView = new ProductView();

        $value = 'test';
        $productView->set('attr1', $value);

        self::assertTrue($productView->has('attr1'));
        self::assertTrue(isset($productView->attr1));

        self::assertSame($value, $productView->get('attr1'));
        self::assertSame($value, $productView->attr1);
    }

    public function testSetAttributeViaMagicMethod(): void
    {
        $productView = new ProductView();

        $value = 'test';
        $productView->attr1 = $value;
        self::assertSame($value, $productView->attr1);
    }

    public function testRemoveAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "attr1" attribute does not exist.');

        $productView = new ProductView();

        $productView->set('attr1', 'test');
        self::assertTrue($productView->has('attr1'));

        $productView->remove('attr1');
        $productView->get('attr1');
    }

    public function testRemoveAttributeViaMagicMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The "attr1" attribute does not exist.');

        $productView = new ProductView();

        $productView->set('attr1', 'test');
        self::assertTrue($productView->has('attr1'));

        unset($productView->attr1);
        $productView->get('attr1');
    }

    public function testToString(): void
    {
        $productView = new ProductView();

        self::assertSame('', (string)$productView);

        $productView->set('sku', null);
        self::assertSame('', (string)$productView);

        $productView->set('sku', '');
        self::assertSame('', (string)$productView);

        $productView->set('sku', 'test_sku');
        self::assertSame('test_sku', (string)$productView);

        $productView->set('name', null);
        self::assertSame('test_sku', (string)$productView);

        $productView->set('name', '');
        self::assertSame('test_sku', (string)$productView);

        $productView->set('name', 'test name');
        self::assertSame('test name', (string)$productView);
    }

    public function testIterable(): void
    {
        $productView = new ProductView();
        $productView->set('id', 123);
        $productView->set('sku', 'test-1');

        self::assertInstanceOf(\Traversable::class, $productView);
        self::assertSame(['id' => 123, 'sku' =>'test-1'], iterator_to_array($productView));
    }
}
