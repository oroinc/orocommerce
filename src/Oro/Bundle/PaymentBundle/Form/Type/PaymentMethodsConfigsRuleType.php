<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistryInterface;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodsConfigsRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_payment_methods_configs_rule';

    /**
     * @var PaymentMethodProvidersRegistryInterface
     */
    protected $methodRegistry;

    /**
     * @var PaymentMethodViewProvidersRegistryInterface
     */
    protected $methodViewRegistry;

    /**
     * @param PaymentMethodProvidersRegistryInterface $methodRegistry
     * @param PaymentMethodViewProvidersRegistryInterface $methodViewRegistry
     */
    public function __construct(
        PaymentMethodProvidersRegistryInterface $methodRegistry,
        PaymentMethodViewProvidersRegistryInterface $methodViewRegistry
    ) {
        $this->methodRegistry = $methodRegistry;
        $this->methodViewRegistry = $methodViewRegistry;
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
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['methods'] = $this->getMethods();
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
     * @return array
     */
    protected function getMethods()
    {
        return array_reduce(
            $this->methodRegistry->getPaymentMethodProviders(),
            function (array $result, PaymentMethodProviderInterface $provider) {
                foreach ($provider->getPaymentMethods() as $method) {
                    $result[$method->getIdentifier()] = $this
                        ->methodViewRegistry->getPaymentMethodView($method->getIdentifier())
                        ->getAdminLabel();
                }
                return $result;
            },
            []
        );
    }
}
