<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Acl\AccessRule;

use Oro\Bundle\FixedProductShippingBundle\Acl\AccessRule\PriceAttributePriceListAccessRule;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use PHPUnit\Framework\TestCase;

class PriceAttributePriceListAccessRuleTest extends TestCase
{
    private PriceAttributePriceListAccessRule $accessRule;
    private Criteria $criteria;

    protected function setUp(): void
    {
        $this->accessRule = new PriceAttributePriceListAccessRule();
        $this->criteria = new Criteria('ORM', PriceAttributePriceList::class, 'test');
    }

    public function testIsNotApplicable(): void
    {
        $this->assertFalse($this->accessRule->isApplicable($this->criteria));
    }

    public function testIsApplicable(): void
    {
        $this->criteria->setOption(PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES, true);
        $this->assertTrue($this->accessRule->isApplicable($this->criteria));
    }

    public function testCanAddConditionWhenIsApplicable(): void
    {
        $this->accessRule->process($this->criteria);

        $expected = new Comparison(
            new Path('name', $this->criteria->getAlias()),
            Comparison::NEQ,
            LoadPriceAttributePriceListData::SHIPPING_COST_NAME
        );

        $this->assertEquals($expected, $this->criteria->getExpression());
    }
}
