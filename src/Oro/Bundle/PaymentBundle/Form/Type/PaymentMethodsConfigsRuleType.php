<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Form\EventSubscriber\DestinationCollectionTypeSubscriber;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodGroupAwareInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\RuleBundle\Form\Type\RuleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;

/**
 * Used for creating and editing payment method config rules.
 */
class PaymentMethodsConfigsRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_payment_methods_configs_rule';

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @var PaymentMethodViewProviderInterface
     */
    protected $methodViewProvider;

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentMethodViewProviderInterface $methodViewProvider
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->methodViewProvider = $methodViewProvider;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('methodConfigs', PaymentMethodConfigCollectionType::class, [
                'required' => false,
            ])
            ->add('destinations', PaymentMethodsConfigsRuleDestinationCollectionType::class, [
                'required'             => false,
                'label'                => 'oro.payment.paymentmethodsconfigsrule.destinations.label',
                'show_form_when_empty' => false,
            ])
            ->add('currency', CurrencySelectionType::class, [
                'label'       => 'oro.payment.paymentmethodsconfigsrule.currency.label',
                'placeholder' => 'oro.currency.currency.form.choose',
            ])
            ->add('rule', RuleType::class, [
                'label' => 'oro.payment.paymentmethodsconfigsrule.rule.label',
            ])
            ->add('method', ChoiceType::class, [
                'mapped'  => false,
                'choices' => $this->getMethods($options['payment_method_group']),
            ]);

        $builder->addEventSubscriber(new DestinationCollectionTypeSubscriber());
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $choices = $form->get('method')->getConfig()->getOption('choices');
        $view->vars['methods'] = array_flip($choices);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentMethodsConfigsRule::class,
            'validation_groups' => [Constraint::DEFAULT_GROUP, 'PaymentMethodsConfigsRule'],
        ]);

        $resolver
            ->define('payment_method_group')
            ->allowedTypes('string')
            ->default('');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * @return array<string,string>
     */
    private function getMethods(string $paymentMethodGroup): array
    {
        $result = [];

        /** @var PaymentMethodInterface|PaymentMethodGroupAwareInterface $paymentMethod */
        foreach ($this->paymentMethodProvider->getPaymentMethods() as $paymentMethod) {
            if ($paymentMethod instanceof PaymentMethodGroupAwareInterface &&
                !$paymentMethod->isApplicableForGroup($paymentMethodGroup)) {
                continue;
            }

            $identifier = $paymentMethod->getIdentifier();
            $label = $this->methodViewProvider
                ->getPaymentMethodView($identifier)
                ->getAdminLabel();

            // In case there are methods with the same label we add spaces as suffix to show them
            while (isset($result[$label])) {
                $label .= ' ';
            }

            $result[$label] = $identifier;
        }

        return $result;
    }
}
