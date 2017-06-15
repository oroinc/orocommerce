<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Form\DataMapper\DiscountConfigurationDataMapper;
use Oro\Bundle\PromotionBundle\Provider\DiscountFormTypeProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BasicDiscountFormType extends AbstractType
{
    const NAME = 'oro_basic_discount';
    const DISCOUNT_FIELD = 'discount';
    const TYPE_FIELD_CHOICES = [DiscountInterface::TYPE_AMOUNT, DiscountInterface::TYPE_PERCENT];

    /**
     * @var DiscountFormTypeProvider
     */
    private $discountFormTypeProvider;

    /**
     * @param DiscountFormTypeProvider $discountFormTypeProvider
     */
    public function __construct(DiscountFormTypeProvider $discountFormTypeProvider)
    {
        $this->discountFormTypeProvider = $discountFormTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::DISCOUNT_FIELD,
                ChoiceType::class,
                [
                    'choices' => $this->getDiscountChoices(),
                    'mapped' => false,
                    'label' => 'oro.promotion.form.basic_discount.discount.label',
                    'required' => false,
                    'placeholder' => false
                ]
            )
            ->add(
                AbstractDiscount::DISCOUNT_TYPE,
                ChoiceType::class,
                [
                    'choices' => $this->getTypeChoices(),
                    'mapped' => false,
                    'label' => 'oro.promotion.form.basic_discount.type.label',
                    'required' => true,
                    'placeholder' => false,
                    'tooltip' => 'oro.promotion.form.basic_discount.type.tooltip',
                ]
            )
            ->add(
                AbstractDiscount::DISCOUNT_VALUE,
                PriceType::class,
                [
                    'currency_empty_value' => null,
                    'required' => true,
                    'label' => 'oro.promotion.form.basic_discount.value.label',
                    'compact' => true,
                ]
            )
            ->setDataMapper(
                new DiscountConfigurationDataMapper()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => DiscountConfiguration::class
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * @return array
     */
    private function getDiscountChoices()
    {
        $formTypes = $this->discountFormTypeProvider->getFormTypes();
        $choices = [];
        foreach ($formTypes as $type => $formType) {
            $choices[$type] = 'oro.promotion.form.basic_discount.discount.choices.' . $type;
        }

        return $choices;
    }

    /**
     * @return array
     */
    private function getTypeChoices()
    {
        $choices = [];
        foreach (self::TYPE_FIELD_CHOICES as $type) {
            $choices[$type] = 'oro.promotion.form.basic_discount.type.choices.' . $type;
        }

        return $choices;
    }
}
