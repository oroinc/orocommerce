<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class FrontendAccountUserType extends AbstractType
{
    const NAME = 'orob2b_customer_frontend_account_user';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstName',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.customer.accountuser.first_name.label'
                ]
            )
            ->add(
                'lastName',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.customer.accountuser.last_name.label'
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'required' => true,
                    'label' => 'orob2b.customer.accountuser.email.label'
                ]
            );

        $passwordOptions = [
            'type'            => 'password',
            'first_options'   => ['label' => 'orob2b.customer.accountuser.password.label'],
            'second_options'  => ['label' => 'orob2b.customer.accountuser.password_confirmation.label'],
            'invalid_message' => $this->translator->trans('orob2b.customer.message.password_mismatch')
        ];

        $data = $builder->getData();
        if ($data instanceof AccountUser && $data->getId()) {
            $passwordOptions = array_merge($passwordOptions, ['required' => false, 'validation_groups' => false]);
        } else {
            $passwordOptions = array_merge($passwordOptions, ['required' => true, 'validation_groups' => 'create']);
        }

        $builder->add(
            'plainPassword',
            'repeated',
            $passwordOptions
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'           => 'OroB2B\Bundle\CustomerBundle\Entity\AccountUser',
            'intention'            => 'account_user',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
