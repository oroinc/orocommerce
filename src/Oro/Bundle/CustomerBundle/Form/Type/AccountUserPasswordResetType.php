<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountUserPasswordResetType extends AbstractType
{
    const NAME = 'oro_account_account_user_password_reset';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'plainPassword',
            'repeated',
            [
                'type' => 'password',
                'first_options' => ['label' => 'oro.customer.accountuser.password.label'],
                'second_options' => ['label' => 'oro.customer.accountuser.password_confirmation.label'],
                'invalid_message' => 'oro.customer.message.password_mismatch',
                'required' => true,
                'validation_groups' => ['create']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention'  => 'account_user_reset',
            'dynamic_fields_disabled' => true
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

    /**
     * @param string $dataClass
     * @return AccountUserPasswordResetType
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;

        return $this;
    }
}
