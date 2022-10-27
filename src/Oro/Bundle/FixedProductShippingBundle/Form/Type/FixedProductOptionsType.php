<?php

namespace Oro\Bundle\FixedProductShippingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Form type for fixed product options in shipping rules.
 */
class FixedProductOptionsType extends AbstractType
{
    public const BLOCK_PREFIX = 'oro_fixed_product_options_type';

    private RoundingServiceInterface $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(FixedProductMethodType::SURCHARGE_TYPE, ChoiceType::class, [
                'label' => 'oro.fixed_product.method.surcharge_type.label',
                'choices' => [
                    'oro.fixed_product.method.surcharge_type.values.percent'
                    => FixedProductMethodType::PERCENT,
                    'oro.fixed_product.method.surcharge_type.values.fixed_amount'
                    => FixedProductMethodType::FIXED_AMOUNT,
                ],
                'attr' => ['class' => 'fixed-product-surcharge-type']
            ])
            ->add(FixedProductMethodType::SURCHARGE_AMOUNT, NumberType::class, [
                'label' => 'oro.fixed_product.method.surcharge_amount.label',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Type(['type' => 'numeric'])
                ],
                'scale' => $this->roundingService->getPrecision(),
                'rounding_mode' => $this->roundingService->getRoundType(),
                'attr' => [
                    'data-scale' => $this->roundingService->getPrecision()
                ]
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!$data || $this->isPercentSurchargeType($data)) {
            $form
                ->add(FixedProductMethodType::SURCHARGE_ON, ChoiceType::class, [
                    'label' => 'oro.fixed_product.method.surcharge_on.label',
                    'choices' => [
                        'oro.fixed_product.method.surcharge_on.values.product_shipping_cost'
                        => FixedProductMethodType::PRODUCT_SHIPPING_COST,
                        'oro.fixed_product.method.surcharge_on.values.product_price'
                        => FixedProductMethodType::PRODUCT_PRICE,
                    ]
                ]);

            FormUtils::replaceField($form, FixedProductMethodType::SURCHARGE_AMOUNT, [
                'label' => 'oro.fixed_product.method.surcharge_amount.percent_label',
                'attr' => [
                    'data-scale' => $this->roundingService->getPrecision(),
                    'data-no-price' => true
                ]
            ]);
        }
    }

    public function onPreSubmit(FormEvent $event): void
    {
        if ($this->isPercentSurchargeType($event->getData())) {
            $event->getForm()->setData($event->getData());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => 'oro.fixed_product.form.oro_fixed_product_options_type.label',
            'allow_extra_fields' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }

    protected function isPercentSurchargeType(array $data): bool
    {
        return $data[FixedProductMethodType::SURCHARGE_TYPE] === FixedProductMethodType::PERCENT;
    }
}
