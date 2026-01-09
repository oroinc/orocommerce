<?php

namespace Oro\Bundle\UPSBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * UPS shipping method options form type.
 */
class UPSShippingMethodOptionsType extends AbstractType
{
    public const BLOCK_PREFIX = 'oro_ups_shipping_method_config_options';

    /**
     * @var RoundingServiceInterface
     */
    protected $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(UPSShippingMethod::OPTION_SURCHARGE, NumberType::class, [
            'required' => true,
            'label' => 'oro.ups.form.shipping_method_config_options.surcharge.label',
            'scale' => $this->roundingService->getPrecision(),
            'rounding_mode' => $this->roundingService->getRoundType(),
            'tooltip' => 'oro.ups.form.shipping_method_config_options.surcharge.tooltip',
            'attr' => [
                'data-scale' => $this->roundingService->getPrecision(),
                'class' => 'method-options-surcharge'
            ],
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }
}
