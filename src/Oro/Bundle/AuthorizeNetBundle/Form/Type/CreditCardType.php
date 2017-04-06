<?php

namespace Oro\Bundle\AuthorizeNetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\PaymentBundle\Form\Type\CreditCardType as ParentCreditCardType;

class CreditCardType extends AbstractType
{
    const NAME = 'oro_authorize_net_credit_card';

    public function getParent()
    {
        return ParentCreditCardType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => 'oro.authorize_net.methods.credit_card.label',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
