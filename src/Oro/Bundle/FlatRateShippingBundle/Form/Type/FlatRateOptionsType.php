<?php

namespace Oro\Bundle\FlatRateShippingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Form type for configuring flat rate shipping method options.
 *
 * Provides form fields for setting the shipping price, handling fee, and processing type
 * (per item or per order) for flat rate shipping methods.
 */
class FlatRateOptionsType extends AbstractType
{
    public const BLOCK_PREFIX = 'oro_flat_rate_options_type';

    /**
     * @var RoundingServiceInterface
     */
    protected $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $priceOptions = [
            'scale' => $this->roundingService->getPrecision(),
            'rounding_mode' => $this->roundingService->getRoundType(),
            'attr' => ['data-scale' => $this->roundingService->getPrecision()],
        ];

        $builder
            ->add(FlatRateMethodType::PRICE_OPTION, NumberType::class, array_merge([
                'label' => 'oro.flat_rate.method.price.label',
                'constraints' => [new NotBlank(), new Type(['type' => 'numeric'])]
            ], $priceOptions))
            ->add(FlatRateMethodType::HANDLING_FEE_OPTION, NumberType::class, array_merge([
                'label' => 'oro.flat_rate.method.handling_fee.label',
                'required' => false,
                'constraints' => [new Type(['type' => 'numeric'])]
            ], $priceOptions))
            ->add(FlatRateMethodType::TYPE_OPTION, ChoiceType::class, [
                'choices' => [
                    'oro.flat_rate.method.processing_type.per_item.label'
                        => FlatRateMethodType::PER_ITEM_TYPE,
                    'oro.flat_rate.method.processing_type.per_order.label'
                        => FlatRateMethodType::PER_ORDER_TYPE,
                ],
                'label' => 'oro.flat_rate.method.processing_type.label',
            ]);
    }

    /**
     * @throws AccessException
     */
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'oro.flat_rate.form.oro_flat_rate_options_type.label',
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}
