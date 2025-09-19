<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\DataGrid;

use Oro\Bundle\DataGridBundle\Datagrid\AbstractColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusLabelVirtualFieldProvider;
use Symfony\Component\Form\Guess\Guess;

/**
 * Provides options for the payment status label virtual field column in datagrid.
 * This column displays a human-readable label for the payment status.
 * Payment status label virtual field is defined in the {@link PaymentStatusLabelVirtualFieldProvider}
 */
class PaymentStatusLabelColumnOptionsGuesser extends AbstractColumnOptionsGuesser
{
    public function __construct(
        private readonly string $entityClass,
        private readonly string $paymentStatusTemplate = '@OroPayment/DataGrid/Property/paymentStatusLabel.html.twig'
    ) {
    }

    #[\Override]
    public function guessFormatter($class, $property, $type): ?ColumnGuess
    {
        if (!is_a($class, $this->entityClass, true) ||
            $property !== PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME) {
            return null;
        }

        $options = [
            'type' => 'twig',
            'frontend_type' => 'html',
            'template' => $this->paymentStatusTemplate,
        ];

        return new ColumnGuess($options, Guess::HIGH_CONFIDENCE);
    }

    #[\Override]
    public function guessSorter($class, $property, $type): ?ColumnGuess
    {
        if (!is_a($class, $this->entityClass, true) ||
            $property !== PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME) {
            return null;
        }

        return new ColumnGuess([
            'data_name' => 'entity.paymentStatus',
        ], Guess::HIGH_CONFIDENCE);
    }

    #[\Override]
    public function guessFilter($class, $property, $type): ?ColumnGuess
    {
        if (!is_a($class, $this->entityClass, true) ||
            $property !== PaymentStatusLabelVirtualFieldProvider::VIRTUAL_FIELD_NAME) {
            return null;
        }

        return new ColumnGuess([
            'type' => PaymentStatusFilter::NAME,
            'data_name' => 'entity.paymentStatus',
        ], Guess::HIGH_CONFIDENCE);
    }
}
