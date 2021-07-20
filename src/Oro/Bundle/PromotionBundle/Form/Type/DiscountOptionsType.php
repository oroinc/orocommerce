<?php

namespace Oro\Bundle\PromotionBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Form\Type\MultiCurrencyType;
use Oro\Bundle\FormBundle\Form\Type\OroPercentType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Form\DataMapper\DiscountConfigurationDataMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class DiscountOptionsType extends AbstractType
{
    const NAME = 'oro_promotion_discount_options';
    const AMOUNT_DISCOUNT_VALUE_FIELD = 'amount_discount_value';
    const PERCENT_DISCOUNT_VALUE_FIELD = 'percent_discount_value';
    const TYPE_FIELD_CHOICES = [DiscountInterface::TYPE_AMOUNT, DiscountInterface::TYPE_PERCENT];

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                AbstractDiscount::DISCOUNT_TYPE,
                ChoiceType::class,
                [
                    'choices' => $options['type_choices'],
                    'mapped' => false,
                    'label' => 'oro.discount_options.general.type.label',
                    'required' => true,
                    'placeholder' => false,
                    'tooltip' => 'oro.discount_options.general.type.tooltip',
                ]
            )
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit'])
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData'])
            ->setDataMapper(
                new DiscountConfigurationDataMapper()
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $selectorPattern = '[name*="[%s]"]';
        $resolver->setRequired(['page_component', 'page_component_options']);
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('page_component_options', 'array');

        $resolver->setDefaults(
            [
                'type_choices' => $this->getTypeChoices(),
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [
                    'view' => 'oropromotion/js/app/views/type-value-switcher',
                    'amount_type_value' => DiscountInterface::TYPE_AMOUNT,
                    'percent_type_value' => DiscountInterface::TYPE_PERCENT,
                    'type_selector' => sprintf($selectorPattern, AbstractDiscount::DISCOUNT_TYPE),
                    'amount_discount_value_selector' => sprintf($selectorPattern, self::AMOUNT_DISCOUNT_VALUE_FIELD),
                    'percent_discount_value_selector' => sprintf($selectorPattern, self::PERCENT_DISCOUNT_VALUE_FIELD),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-page-component-module'] = $options['page_component'];
        $view->vars['attr']['data-page-component-options'] = json_encode($options['page_component_options']);
    }

    /**
     * Based on the submission, mark as not required the Discount Type value that were hidden and is empty.
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (!isset($data['discount_type'])) {
            return;
        }

        if (DiscountInterface::TYPE_AMOUNT === $data['discount_type']) {
            FormUtils::replaceField(
                $event->getForm(),
                self::PERCENT_DISCOUNT_VALUE_FIELD,
                ['required' => false, 'constraints' => []]
            );
        } elseif (DiscountInterface::TYPE_PERCENT === $data['discount_type']) {
            FormUtils::replaceField(
                $event->getForm(),
                self::AMOUNT_DISCOUNT_VALUE_FIELD,
                ['required' => false, 'attr' => ['class' => 'hide'], 'value_constraints' => []]
            );
            FormUtils::replaceField(
                $event->getForm(),
                self::PERCENT_DISCOUNT_VALUE_FIELD,
                ['attr' => []]
            );
        }
    }

    /**
     * Based on the available data hide Discount Type value field, that is empty.
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();

        if (empty($data)) {
            $this->addValueFields($event->getForm());

            return;
        }

        if (DiscountInterface::TYPE_AMOUNT === $data[AbstractDiscount::DISCOUNT_TYPE]) {
            $this->addValueFields($event->getForm());
        } else {
            $this->addValueFields($event->getForm(), false, true);
        }
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
    private function getTypeChoices()
    {
        $choices = [];
        foreach (self::TYPE_FIELD_CHOICES as $type) {
            $choices['oro.discount_options.general.type.choices.' . $type] = $type;
        }

        return $choices;
    }

    /**
     * @param FormInterface $form
     * @param bool $amountFieldVisible
     * @param bool $percentFieldVisible
     */
    private function addValueFields(FormInterface $form, $amountFieldVisible = true, $percentFieldVisible = false)
    {
        $form
            ->add(
                self::AMOUNT_DISCOUNT_VALUE_FIELD,
                MultiCurrencyType::class,
                [
                    'currency_empty_value' => null,
                    'required' => true,
                    'label' => 'oro.discount_options.general.value.label',
                    'tooltip' => 'oro.discount_options.general.value.tooltip',
                    'compact' => false,
                    'data_class' => MultiCurrency::class,
                    'value_constraints' => [new NotBlank()],
                    'attr' => $amountFieldVisible ? [] : ['class' => 'hide'],
                ]
            )
            ->add(
                self::PERCENT_DISCOUNT_VALUE_FIELD,
                OroPercentType::class,
                [
                    'required' => true,
                    'label' => 'oro.discount_options.general.value.label',
                    'constraints' => [new NotBlank(), new Range(['min' => 0, 'max' => 100])],
                    'attr' => $percentFieldVisible ? [] : ['class' => 'hide'],
                ]
            );
    }
}
