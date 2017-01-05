<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentMethodConfigType extends AbstractType
{
    const NAME = 'oro_payment_method_config';

    /**
     * @var PaymentMethodProvidersRegistry
     */
    protected $methodRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param PaymentMethodProvidersRegistry $methodRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PaymentMethodProvidersRegistry $methodRegistry,
        TranslatorInterface $translator
    ) {
        $this->methodRegistry = $methodRegistry;
        $this->translator = $translator;
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
            $this->methodRegistry->getPaymentMethods(),
            function (array $result, PaymentMethodInterface $method) {
                $type = $method->getType();
                $result[$type] = $this->translator->trans(sprintf('oro.payment.admin.%s.label', $type));
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
