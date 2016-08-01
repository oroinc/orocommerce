<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;

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
            ->add('value', 'number', array_merge([
                'required' => true,
                'label' => 'orob2b.shipping.flatrateruleconfiguration.value.label',
            ], $priceOptions))
            ->add('handlingFeeValue', 'number', array_merge([
                'label' => 'orob2b.shipping.flatrateruleconfiguration.handling_fee_value.label',
            ], $priceOptions))
            ->add('processingType', 'choice', [
                'choices' => [
                    FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ITEM
                    => 'orob2b.shipping.flatrateruleconfiguration.processing_type.per_item.label',
                    FlatRateRuleConfiguration::PROCESSING_TYPE_PER_ORDER
                    => 'orob2b.shipping.flatrateruleconfiguration.processing_type.per_order.label',
                ],
                'label' => 'orob2b.shipping.flatrateruleconfiguration.processing_type.label',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FlatRateRuleConfiguration::class,
            'label' => 'orob2b.shipping.form.orob2b_shipping_flat_rate_rule_config.label',
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ShippingRuleConfigurationType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
