<?php

namespace Oro\Bundle\DPDBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Type;

class DPDShippingMethodOptionsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_dpd_method_options';

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
            ->add(DPDShippingMethod::HANDLING_FEE_OPTION, NumberType::class, array_merge([
                'label' => 'oro.dpd.form.shipping_method_config_options.handling_fee.label',
                'tooltip' => 'oro.dpd.form.shipping_method_config_options.handling_fee.tooltip',
                'constraints' => [new Type(['type' => 'numeric'])],
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
