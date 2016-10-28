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

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()->getMock();

        $criteria = $this->getMock(Criteria::class);

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

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()->getMock();

        $criteria = $this->getMock(Criteria::class);

        $expression = Criteria::expr()->in('inventory_status', $statuses);

        $criteria->expects($this->once())
            ->method('andWhere')
            ->with($expression);

        $query->expects($this->once())
            ->method('getCriteria')
            ->willReturn($criteria);

        $this->modifier->modifyByInventoryStatus($query, $statuses);
    }
}
