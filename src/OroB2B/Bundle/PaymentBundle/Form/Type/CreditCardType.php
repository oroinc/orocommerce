<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class CreditCardType extends AbstractType
{
    const NAME = 'orob2b_payment_credit_card';
    const CONFIG_NAME = 'paypal_payments_pro';

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(
        ConfigManager $configManager
    ) {
        $this->configManager = $configManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'ACCT',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.card_number.label',
                    'mapped' => false
                ]
            )
            ->add(
                'expirationDate',
                'orob2b_payment_credit_card_expiration_date',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.expiration_date.label',
                    'mapped' => false,
                    'placeholder' => [
                        'year' => 'Year',
                        'month' => 'Month'
                    ]
                ]
            )
            ->add(
                'EXPDATE',
                'hidden'
            )
            ->add(
                'CVV2',
                'password',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.cvv2.label',
                    'always_empty' => true,
                    'mapped' => false,
                    'block_name' => 'payment_credit_card_cvv'
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $label = $this->configManager->get('oro_b2b_payment.' . self::CONFIG_NAME . '_label');
        $enabled = $this->configManager->get('oro_b2b_payment.' . self::CONFIG_NAME . '_enabled');

        $resolver->setDefaults(
            [
                'label' => empty($label) ? 'orob2b.payment.methods.credit_card.label' : $label,
                'enabled' => $enabled,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($view->children as $child) {
            $child->vars['full_name'] = $child->vars['name'];
        }

        $view->vars['block_prefixes'] = array_merge(
            $view->vars['block_prefixes'],
            ['payment_method_form']
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
