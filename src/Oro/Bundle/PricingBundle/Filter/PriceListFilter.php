<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\SingleChoiceFilter;
use Oro\Bundle\PricingBundle\Form\Type\Filter\DefaultPriceListFilterType;

class PriceListFilter extends SingleChoiceFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'choice';

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $formView  = $this->getForm()->createView();

        // Allow clearing if filter is not required, disallow otherwise.
        $metadata['allowClear'] = false;
        if (isset($formView->vars['required'])) {
            $metadata['allowClear'] = !$formView->vars['required'];
        }

        // Ensure default value is selected in dropdown.
        if (isset($formView->vars['value']['value'])) {
            $metadata['value'] = ['value' => (string) $formView->vars['value']['value']];
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return DefaultPriceListFilterType::class;
    }
}
