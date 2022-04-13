<?php

namespace Oro\Bundle\FixedProductShippingBundle\Acl\AccessRule;

use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\PricingBundle\Form\Extension\PriceAttributesProductFormExtension;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;

/**
 * Hide shippingCost productPrice attribute for:
 * - 'Product Prices' section on product edit page.
 * - 'Price Attributes' section on product view page.
 */
class PriceAttributePriceListAccessRule implements AccessRuleInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(Criteria $criteria): bool
    {
        return $criteria->getOption(PriceAttributesProductFormExtension::PRODUCT_PRICE_ATTRIBUTES_PRICES, false);
    }

    /**
     * {@inheritdoc}
     */
    public function process(Criteria $criteria): void
    {
        $criteria->andExpression(
            new Comparison(
                new Path('name', $criteria->getAlias()),
                Comparison::NEQ,
                LoadPriceAttributePriceListData::SHIPPING_COST_NAME
            )
        );
    }
}
