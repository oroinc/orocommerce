<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountUserEmailConfirmationType extends AbstractType
{
    const NAME = 'orob2b_customer_account_user_email_confirmation';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'email',
            'email',
            [
                'required' => true,
                'label' => 'orob2b.customer.accountuser.email.label',
                'constraints' => [
                    new NotBlank(),
                    new Email()
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
