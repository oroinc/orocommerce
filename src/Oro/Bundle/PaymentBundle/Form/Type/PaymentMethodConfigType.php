<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodConfigType extends AbstractType
{
    const NAME = 'oro_payment_method_config';

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
        $builder->add(
            'type',
            HiddenType::class,
            [
                'required' => true,
                'label' => 'oro.payment.paymentmethodconfig.type.label',
                'attr' => ['placeholder' => 'oro.payment.paymentmethodconfig.type.label']
            ]
        );
        $builder->add('options', HiddenType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['methods_labels'] = array_reduce(
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

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentMethodConfig::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
