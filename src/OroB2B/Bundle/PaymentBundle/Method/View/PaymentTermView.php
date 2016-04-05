<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm as PaymentTermMethod;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermView implements PaymentMethodViewInterface
{
    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**  @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTerm */
    protected $paymentTerm = false;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface $translator
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    /** {@inheritdoc} */
    public function getOptions()
    {
        if ($this->getPaymentTerm()) {
            return ['value' => $this->getPaymentTerm()->getLabel()];
        }

        return [];
    }

    /** {@inheritdoc} */
    public function getTemplate()
    {
        return 'OroB2BPaymentBundle:PaymentMethod:plain.html.twig';
    }

    /** {@inheritdoc} */
    public function getOrder()
    {
        /* @todo: config */
        return 0;
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->translator->trans('orob2b.payment.methods.term_method.label');
    }

    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PaymentTermMethod::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentTerm()
    {
        if (false !== $this->paymentTerm) {
            return $this->paymentTerm;
        }

        $token = $this->tokenStorage->getToken();

        /** @var AccountUser $user */
        if ($token && ($user = $token->getUser()) instanceof AccountUser) {
            $this->paymentTerm = $this->paymentTermProvider->getPaymentTerm($user->getAccount());
        }

        return $this->paymentTerm;
    }
}
