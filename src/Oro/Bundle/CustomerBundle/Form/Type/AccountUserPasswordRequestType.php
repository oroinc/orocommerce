<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountUserPasswordRequestType extends AbstractType
{
    const NAME = 'oro_account_account_user_password_request';

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
                'label' => 'oro.customer.accountuser.email.label',
                'constraints' => [
                    new NotBlank(),
                    new Email()
                ],
                'attr' => [
                    'placeholder' => 'oro.customer.accountuser.placeholder.email'
                ]
            ]
        );
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
