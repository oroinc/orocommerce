<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductVisibilitySearchQueryModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    protected $modifier;

    public function setUp()
    {
        $this->modifier = new ProductVisibilitySearchQueryModifier();
    }

    public function testModifyByStatus()
    {
        $statuses = ['enabled', 'disabled'];

        $implodedStatuses = implode(', ', $statuses);

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()->getMock();

        $criteria = $this->getMock(Criteria::class);

        $expression = Criteria::expr()->contains('status', $implodedStatuses);

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

        $implodedStatuses = implode(', ', $statuses);

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()->getMock();

        $criteria = $this->getMock(Criteria::class);

        $expression = Criteria::expr()->contains('inventory_status', $implodedStatuses);

        $criteria->expects($this->once())
            ->method('andWhere')
            ->with($expression);

        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->modifier->modifyByInventoryStatus($query, $statuses);
    }
}
