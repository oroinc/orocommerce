<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FrontendAccountUserRegistrationType extends AbstractType
{
    const NAME = 'orob2b_account_frontend_account_user_register';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstName',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.account.accountuser.first_name.label'
                ]
            )
            ->add(
                'lastName',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.account.accountuser.last_name.label'
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'required' => true,
                    'label' => 'orob2b.account.accountuser.email.label'
                ]
            );

        $builder->add(
            'plainPassword',
            'repeated',
            [
                'type' => 'password',
                'first_options' => ['label' => 'orob2b.account.accountuser.password.label'],
                'second_options' => ['label' => 'orob2b.account.accountuser.password_confirmation.label'],
                'invalid_message' => 'orob2b.account.message.password_mismatch',
                'required' => true,
                'validation_groups' => ['create']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'account_user'
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $dataClass
     * @return FrontendAccountUserType
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;

        return $this;
    }
}
