<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\ShippingBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentMethodsConfigsRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_payment_methods_configs_rule';

    /**
     * @var PaymentMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param PaymentMethodRegistry $methodRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(PaymentMethodRegistry $methodRegistry, TranslatorInterface $translator)
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
            ->add('methodConfigs', PaymentMethodConfigCollectionType::class, [
                'required' => false
            ])
            ->add('destinations', PaymentMethodsConfigsRuleDestinationCollectionType::class, [
                'required' => false,
                'label'    => 'oro.payment.paymentmethodsconfigsrule.destinations.label',
            ])
            ->add('currency', CurrencySelectionType::class, [
                'label'       => 'oro.payment.paymentmethodsconfigsrule.currency.label',
                'empty_value' => 'oro.currency.currency.form.choose',
            ])
            ->add('rule', RuleType::class, [
                'label' => 'oro.payment.paymentmethodsconfigsrule.rule.label',
            ])
            ->add('method', ChoiceType::class, [
                'mapped'  => false,
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
            'data_class' => PaymentMethodsConfigsRule::class,
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
            $this->methodRegistry->getPaymentMethods(),
            function (array $result, PaymentMethodInterface $method) use ($translate) {
                $type = $method->getType();
                $result[$type] = $translate ?
                    $this->translator->trans(sprintf('oro.payment.admin.%s.label', $type)) : $type;

                return $result;
            },
            []
        );
    }
}
