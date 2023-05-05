<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductVisibilitySearchQueryModifierTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVisibilitySearchQueryModifier */
    private $modifier;

    protected function setUp(): void
    {
        $this->modifier = new ProductVisibilitySearchQueryModifier();
    }

    public function testModifyByStatus()
    {
        $statuses = ['enabled', 'disabled'];

        $query = $this->createMock(Query::class);

        $criteria = $this->createMock(Criteria::class);

        $expression = Criteria::expr()->in('status', $statuses);

        $criteria->expects($this->once())
            ->method('andWhere')
            ->with($expression);

        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->modifier->modifyByStatus($query, $statuses);
    }

    public function testModifyByInventoryStatus()
    {
        $statuses = ['in_stock', 'out_of_stock'];

        $query = $this->createMock(Query::class);

        $criteria = $this->createMock(Criteria::class);

        $expression = Criteria::expr()->in('inv_status', $statuses);

        $criteria->expects($this->once())
            ->method('andWhere')
            ->with($expression);

        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->modifier->modifyByInventoryStatus($query, $statuses);
    }
}
