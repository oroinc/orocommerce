<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroUnstructuredHiddenType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
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
    public function __construct(
        private PaymentMethodViewProviderInterface $methodViewProvider
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'type',
            HiddenType::class,
            [
                'required' => true,
                'label' => 'oro.payment.paymentmethodconfig.type.label',
                'attr' => ['placeholder' => 'oro.payment.paymentmethodconfig.type.label'],
            ]
        );
        $builder->add('options', OroUnstructuredHiddenType::class);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['payment_method_label'] = null;

        /** @var PaymentMethodConfig|null $paymentMethodConfig */
        $paymentMethodConfig = $form->getData();
        if ($paymentMethodConfig !== null) {
            if ($this->methodViewProvider->hasPaymentMethodView($paymentMethodConfig->getType())) {
                $view->vars['payment_method_label'] = $this->methodViewProvider
                    ->getPaymentMethodView($paymentMethodConfig->getType())->getAdminLabel();
            }
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentMethodConfig::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_payment_method_config';
    }
}
