<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PaymentBundle\Form\Type\Filter\PaymentStatusFilterType;

/**
 * Represents a filter for payment statuses.
 */
class PaymentStatusFilter extends ChoiceFilter
{
    public const string NAME = 'payment-status';

    #[\Override]
    protected function getFormType(): string
    {
        return PaymentStatusFilterType::class;
    }

    #[\Override]
    public function init($name, array $params): void
    {
        // Overrides the default frontend type to 'select' so the filter component used for this filter
        // will be 'oro/filter/select-filter' instead of 'oro/filter/payment-status-filter' - because we don't need
        // custom JS component for now, it is fine to use the standard select filter component.
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'select';

        parent::init($name, $params);
    }
}
