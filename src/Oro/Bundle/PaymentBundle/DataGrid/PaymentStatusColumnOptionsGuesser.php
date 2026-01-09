<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\DataGrid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusVirtualRelationProvider;
use Symfony\Component\Form\Guess\Guess;

/**
 * Provides options for the payment status column in datagrid.
 * Enables filtering based on the payment status code.
 */
class PaymentStatusColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    #[\Override]
    public function guessFilter($class, $property, $type): ?ColumnGuess
    {
        if (
            !is_a($class, PaymentStatus::class, true) ||
            $property !== PaymentStatusVirtualRelationProvider::VIRTUAL_RELATION_NAME
        ) {
            return null;
        }

        return new ColumnGuess([
            'type' => PaymentStatusFilter::NAME,
            'data_name' => 'entity.paymentStatus',
            'options' => [
                // For the option info - {@see PaymentStatusFilterType}.
                'raw_labels' => true,
            ],
        ], Guess::HIGH_CONFIDENCE);
    }
}
