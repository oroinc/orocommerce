<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermMethodType extends AbstractType
{
    const NAME = 'orob2b_payment_term_method';

    /**
     * @var PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(PaymentTermProvider $paymentTermProvider, TokenStorageInterface $tokenStorage)
    {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'paymentCreditCard',
            'text',
            [
                'required' => true,
                'label' => 'orob2b.payment.payment_credit_card.label'
            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => 'orob2b.payment.methods.term_method.label',
            ]
        );
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $token = $this->tokenStorage->getToken();

        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            $view->vars['paymentTerm'] = $this->paymentTermProvider->getPaymentTerm($user->getAccount())->getLabel();
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
