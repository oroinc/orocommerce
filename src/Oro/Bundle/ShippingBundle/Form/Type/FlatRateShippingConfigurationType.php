<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;

class FlatRateShippingConfigurationType extends AbstractType
{
    const NAME = 'orob2b_shipping_flat_rate_rule_config';

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
            ->add('value', NumberType::class, array_merge([
                'required' => true,
                'label' => 'oro.shipping.flatrateruleconfiguration.value.label',
                'constraints' => [new NotBlank(['groups' => ['Enabled']])]
            ], $priceOptions))
            ->add('handlingFeeValue', NumberType::class, array_merge([
                'label' => 'oro.shipping.flatrateruleconfiguration.handling_fee_value.label',
            ], $priceOptions))
            ->add('processingType', ChoiceType::class, [
                'choices' => [
                    FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ITEM
                    => 'oro.shipping.flatrateruleconfiguration.processing_type.per_item.label',
                    FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER
                    => 'oro.shipping.flatrateruleconfiguration.processing_type.per_order.label',
                ],
                'label' => 'oro.shipping.flatrateruleconfiguration.processing_type.label',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FlatRateRuleConfiguration::class,
            'label' => 'oro.shipping.form.orob2b_shipping_flat_rate_rule_config.label',
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ShippingRuleConfigurationType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
