<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ShippingMethodsConfigsRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_methods_configs_rule';

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ShippingMethodRegistry $methodRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(ShippingMethodRegistry $methodRegistry, TranslatorInterface $translator)
    {
        $this->methodRegistry = $methodRegistry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rule', RuleType::class, ['label' => 'oro.shipping.shippingmethodsconfigsrule.rule.label'])
            ->add('currency', CurrencySelectionType::class, [
                'label' => 'oro.shipping.shippingmethodsconfigsrule.currency.label',
                'empty_value' => 'oro.currency.currency.form.choose',
            ])
            ->add('destinations', CollectionType::class, [
                'required' => false,
                'entry_type' => ShippingMethodsConfigsRuleDestinationType::class,
                'label' => 'oro.shipping.shippingmethodsconfigsrule.destinations.label',
            ])
            ->add('methodConfigs', ShippingMethodConfigCollectionType::class, [
                'required' => false,
            ])
            ->add('method', ChoiceType::class, [
                'mapped' => false,
                'choices' => $this->getMethods(),
            ]);

        $builder->addEventSubscriber(new DestinationCollectionTypeSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['methods'] = $this->getMethods(true);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingMethodsConfigsRule::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * @param bool $translate
     * @return array
     */
    protected function getMethods($translate = false)
    {
        return array_reduce(
            $this->methodRegistry->getShippingMethods(),
            function (array $result, ShippingMethodInterface $method) use ($translate) {
                $label = $method->getLabel();
                if ($translate) {
                    $label = $this->translator->trans($label);
                }
                $result[$method->getIdentifier()] = $label;
                return $result;
            },
            []
        );
    }
}
