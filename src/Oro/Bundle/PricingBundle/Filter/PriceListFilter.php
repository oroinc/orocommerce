<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\SingleChoiceFilter;
use Oro\Bundle\PricingBundle\Form\Type\Filter\DefaultPriceListFilterType;

class PriceListFilter extends SingleChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';
        $params['allowClear'] = false;

        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DefaultPriceListFilterType::NAME;
    }
}
