<?php

namespace Oro\Bundle\FlatRateBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethodType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class FlatRateOptionsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_flat_rate_options_type';

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
            ->add(FlatRateMethodType::PRICE_OPTION, NumberType::class, array_merge([
                'required' => true,
                'label' => 'oro.shipping.method.flat_rate.price.label',
                'constraints' => [new NotBlank(), new Type(['type' => 'numeric'])]
            ], $priceOptions))
            ->add(FlatRateMethodType::HANDLING_FEE_OPTION, NumberType::class, array_merge([
                'label' => 'oro.shipping.method.flat_rate.handling_fee.label',
                'constraints' => [new Type(['type' => 'numeric'])]
            ], $priceOptions))
            ->add(FlatRateMethodType::TYPE_OPTION, ChoiceType::class, [
                'required' => true,
                'choices' => [
                    FlatRateMethodType::PER_ITEM_TYPE
                    => 'oro.shipping.method.flat_rate.processing_type.per_item.label',
                    FlatRateMethodType::PER_ORDER_TYPE
                    => 'oro.shipping.method.flat_rate.processing_type.per_order.label',
                ],
                'label' => 'oro.shipping.method.flat_rate.processing_type.label',
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'oro.shipping.form.oro_shipping_flat_rate_type_options.label',
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
