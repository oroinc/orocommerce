<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\TemplateFixture;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\ImportExport\TemplateFixture\RelatedProductFixture;

class RelatedProductFixtureTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedProductFixture */
    private $fixture;

    protected function setUp(): void
    {
        $this->fixture = new RelatedProductFixture();
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals(RelatedProduct::class, $this->fixture->getEntityClass());
    }

    public function testGetEntity(): void
    {
        $this->assertInstanceOf(RelatedProduct::class, $this->fixture->getEntity('test'));
    }

    public function testGetData(): void
    {
        $this->assertEquals(
            [
                ['sku' => 'sku-1', 'relatedItem' => 'sku-2,sku-3']
            ],
            iterator_to_array($this->fixture->getData())
        );
    }
}
