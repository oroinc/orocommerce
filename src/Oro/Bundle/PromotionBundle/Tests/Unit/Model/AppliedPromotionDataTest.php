<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class AppliedPromotionDataTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 10],
            ['rule', new Rule(), false],
            ['discountConfiguration', new DiscountConfiguration(), false],
            ['useCoupons', true],
            ['productsSegment', new Segment(), false],
        ];

        $this->assertPropertyAccessors(new AppliedPromotionData(), $properties);
    }

    public function testScopesCollection()
    {
        $promotion = new AppliedPromotionData();
        $scope = new Scope();
        $promotion->addScope($scope);
        $this->assertInstanceOf(ArrayCollection::class, $promotion->getScopes());
        $this->assertEquals([$scope], $promotion->getScopes()->toArray());
    }
}
