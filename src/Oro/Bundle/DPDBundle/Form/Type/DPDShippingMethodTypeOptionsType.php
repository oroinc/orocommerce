<?php

namespace Oro\Bundle\DPDBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class DPDShippingMethodTypeOptionsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_dpd_type_options';

    /**
     * @var RoundingServiceInterface
     */
    protected $roundingService;

    /**
     * @param RoundingServiceInterface $roundingService
     */
    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $priceOptions = [
            'scale' => $this->roundingService->getPrecision(),
            'rounding_mode' => $this->roundingService->getRoundType(),
            'attr' => ['data-scale' => $this->roundingService->getPrecision()],
        ];

        $builder
            ->add(DPDShippingMethodType::FLAT_PRICE_OPTION, NumberType::class, array_merge([
                'required' => true,
                'label' => 'oro.dpd.form.shipping_method_config_options.flat_price.label',
                'tooltip' => 'oro.dpd.form.shipping_method_config_options.flat_price.tooltip',
                'constraints' => [new NotBlank(), new Type(['type' => 'numeric'])]
            ], $priceOptions))
            ->add(DPDShippingMethodType::TABLE_PRICE_OPTION, TextareaType::class, [
                'required' => false,
                'label' => 'oro.dpd.form.shipping_method_config_options.table_price.label',
                'tooltip' => 'oro.dpd.form.shipping_method_config_options.table_price.tooltip',
                'constraints' => [] //FIXME: define constraints
            ])
            ->add(DPDShippingMethod::HANDLING_FEE_OPTION, NumberType::class, array_merge([
                'label' => 'oro.dpd.form.shipping_method_config_options.handling_fee.label',
                'tooltip' => 'oro.dpd.form.shipping_method_config_options.handling_fee.tooltip',
                'constraints' => [new Type(['type' => 'numeric'])]
            ], $priceOptions));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
