<?php

namespace Oro\Bundle\DPDBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

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
        $builder
            ->add(DPDShippingMethod::HANDLING_FEE_OPTION, NumberType::class, [
                'required' => true,
                'label' => 'oro.dpd.form.shipping_method_config_options.handling_fee.label',
                'scale' => $this->roundingService->getPrecision(),
                'rounding_mode' => $this->roundingService->getRoundType(),
                'attr' => [
                    'data-scale' => $this->roundingService->getPrecision(),
                    'class' => 'method-options-surcharge',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
