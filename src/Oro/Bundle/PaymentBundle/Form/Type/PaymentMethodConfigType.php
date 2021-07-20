<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroUnstructuredHiddenType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for PaymentMethodConfig entity
 */
class PaymentMethodConfigType extends AbstractType
{
    const NAME = 'oro_payment_method_config';

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $methodProvider;

    /**
     * @var PaymentMethodViewProviderInterface
     */
    protected $methodViewProvider;

    public function __construct(
        PaymentMethodProviderInterface $methodProvider,
        PaymentMethodViewProviderInterface $methodViewProvider
    ) {
        $this->methodProvider = $methodProvider;
        $this->methodViewProvider = $methodViewProvider;
    }

    /**
     * {@inheritDoc}
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
        $builder->add('options', OroUnstructuredHiddenType::class);
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $result = [];
        foreach ($this->methodProvider->getPaymentMethods() as $method) {
            $methodId = $method->getIdentifier();
            $result[$methodId] = $this
                ->methodViewProvider->getPaymentMethodView($methodId)
                ->getAdminLabel();
        }

        $view->vars['methods_labels'] = $result;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentMethodConfig::class,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
