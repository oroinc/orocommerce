<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CollectionSortOrderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var Segment */
    private $segment;

    /** @var SegmentSnapshot */
    private $entity;

    protected function setUp(): void
    {
        $this->product = new Product();
        $this->segment = new Segment();
        $this->entity = new CollectionSortOrder();
        $this->entity->setProduct($this->product);
        $this->entity->setSegment($this->segment);
    }

    public function testSettersAndGetters()
    {
        $this->assertNull($this->entity->getId());
        $this->assertNull($this->entity->getSortOrder());
        $this->assertNotNull($this->entity->getProduct());
        $this->assertSame($this->product, $this->entity->getProduct());
        $this->assertNotNull($this->entity->getSegment());
        $this->assertSame($this->segment, $this->entity->getSegment());

        $sortOrderValue = 0.25;
        $this->entity->setProduct(new Product());
        $this->entity->setSegment(new Segment());
        $this->entity->setSortOrder($sortOrderValue);

        $this->assertEqualsWithDelta($sortOrderValue, $this->entity->getSortOrder(), 1e-6);
        $this->assertNotSame($this->product, $this->entity->getProduct());
        $this->assertNotSame($this->segment, $this->entity->getSegment());
    }
}
