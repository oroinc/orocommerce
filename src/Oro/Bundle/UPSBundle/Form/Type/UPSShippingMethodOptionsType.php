<?php

namespace Oro\Bundle\UPSBundle\Form\Type;

use Oro\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class UPSShippingMethodOptionsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_ups_shipping_method_config_options';

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
        $builder->add('surcharge', NumberType::class, [
            'required' => true,
            'label' => 'oro.ups.form.shipping_method_config_options.surcharge.label',
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
