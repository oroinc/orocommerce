<?php

namespace Oro\Bundle\FedexShippingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class FedexShippingMethodOptionsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_fedex_shipping_method_options';

    /**
     * @var RoundingServiceInterface
     */
    protected $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(FedexShippingMethod::OPTION_SURCHARGE, NumberType::class, [
            'required' => true,
            'label' => 'oro.fedex.shipping_method_options.surcharge.label',
            'scale' => $this->roundingService->getPrecision(),
            'rounding_mode' => $this->roundingService->getRoundType(),
            'attr' => [
                'data-scale' => $this->roundingService->getPrecision(),
                'class' => 'method-options-surcharge'
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
