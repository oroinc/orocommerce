<?php

namespace Oro\Bundle\PaymentBundle\Form\Type\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractChoiceType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\PaymentBundle\Filter\PaymentStatusFilter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Filter form type for the payment status filter - {@see PaymentStatusFilter}.
 */
class PaymentStatusFilterType extends AbstractChoiceType
{
    private PaymentStatusLabelFormatter $paymentStatusLabelFormatter;

    public function __construct(
        TranslatorInterface $translator,
        PaymentStatusLabelFormatter $paymentStatusLabelFormatter
    ) {
        parent::__construct($translator);

        $this->paymentStatusLabelFormatter = $paymentStatusLabelFormatter;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_payment_status_filter';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceFilterType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->define('target_entity')
            ->default(null)
            ->allowedTypes('string', 'null')
            ->info(
                'The class of the entity for which the payment statuses are filtered. '
                . 'If not set, the filter will use only the payment statuses available out-of-the-box.'
            );

        $resolver
            ->define('raw_labels')
            ->default(false)
            ->allowedTypes('boolean')
            ->info('If true, the filter will include payment status codes instead of labels.');

        $resolver->setNormalizer('field_options', function (Options $options, $value) {
            if (!empty($value['choices'])) {
                // Choices are already set, no need to override them.
                return $value;
            }

            $targetClass = $options->offsetGet('target_entity');
            $value['choices'] = $this->paymentStatusLabelFormatter->getAvailableStatuses($targetClass);
            $value['translatable_options'] = $value['translatable_options'] ?? false;
            if ($options['raw_labels']) {
                $value['choices'] = array_values($value['choices']);
                $value['choices'] = array_combine($value['choices'], $value['choices']);
            }

            return $value;
        });
    }
}
