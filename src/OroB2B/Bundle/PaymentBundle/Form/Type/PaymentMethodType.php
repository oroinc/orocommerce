<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormFactory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Form\PaymentMethodTypeRegistry;

class PaymentMethodType extends AbstractType
{
    const NAME = 'orob2b_payment_method';

    /** @var PaymentMethodTypeRegistry */
    private $registry;

    /** @var FormFactory */
    private $formFactory;

    /** @var ConfigManager */
    protected $configManager;

    /** @var array */
    private $paymentMethodTypes;

    /**
     * @param PaymentMethodTypeRegistry $registry
     * @param FormFactory $formFactory
     * @param ConfigManager $configManager
     */
    public function __construct(
        PaymentMethodTypeRegistry $registry,
        FormFactory $formFactory,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->formFactory = $formFactory;
        $this->configManager = $configManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->paymentMethodTypes = $this->registry->getPaymentMethodTypes();

        $builder
            ->add(
                'paymentMethod',
                'choice',
                [
                    'choices' => $this->paymentMethodTypes,
                    'choices_as_values' => true,
                    'choice_value' => function ($value) {
                        return empty($value) ? '' : $value::NAME;
                    },
                    'choice_label' => function ($value, $key, $index) {
                        return $key;
                    },
                    'expanded' => true,
                    'multiple' => false,
                    'label' => 'Select a Payment Method'
                ]
            );
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($view->children['paymentMethod']->children as $methodRadioView) {
            /** @var FormInterface $subform */
            $subform = $this->formFactory->create($methodRadioView->vars['value']);
            $methodRadioView->vars['subform'] = $subform->createView();

            $label = $this->configManager->get('oro_b2b_payment.' . $subform->getName() . '.label');

            if (empty($label)) {
                $label = $subform->getConfig()->getOption('label');
            }

            if (empty($label)) {
                $label = 'orob2b.payment.methods.' . $subform->getName() . '.label';
            }

            $methodRadioView->vars['label'] = $label;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
