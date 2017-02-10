<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber;
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
     * @var PaymentMethodViewProviderInterface
     */
    protected $methodViewProvider;

    /**
     * @param PaymentMethodProvidersRegistryInterface $methodRegistry
     * @param PaymentMethodViewProviderInterface      $methodViewProvider
     */
    public function __construct(
        PaymentMethodProvidersRegistryInterface $methodRegistry,
        PaymentMethodViewProviderInterface $methodViewProvider
    ) {
        $this->methodRegistry = $methodRegistry;
        $this->methodViewProvider = $methodViewProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('methodConfigs', PaymentMethodConfigCollectionType::class, [
                'required' => false
            ])
            ->add('destinations', PaymentMethodsConfigsRuleDestinationCollectionType::class, [
                'required'             => false,
                'label'                => 'oro.payment.paymentmethodsconfigsrule.destinations.label',
                'show_form_when_empty' => false,
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
     * {@inheritDoc}
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
     * {@inheritDoc}
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
                        ->methodViewProvider->getPaymentMethodView($method->getIdentifier())
                        ->getAdminLabel();
                }
                return $result;
            },
            []
        );
    }
}
