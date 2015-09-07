<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\AddressBundle\Form\Type\AddressCollectionType;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserType extends AbstractType
{
    const NAME = 'orob2b_account_account_user';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $roleClass;

    /**
     * @var string
     */
    protected $addressClass;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $roleClass
     */
    public function setRoleClass($roleClass)
    {
        $this->roleClass = $roleClass;
    }

    /**
     * @param string $addressClass
     */
    public function setAddressClass($addressClass)
    {
        $this->addressClass = $addressClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addEntityFields($builder);

        $data = $builder->getData();

        $passwordOptions = [
            'type'            => 'password',
            'required'        => false,
            'first_options'   => ['label' => 'orob2b.account.accountuser.password.label'],
            'second_options'  => ['label' => 'orob2b.account.accountuser.password_confirmation.label'],
            'invalid_message' => 'orob2b.account.message.password_mismatch',
        ];

        if ($data instanceof AccountUser && $data->getId()) {
            $passwordOptions = array_merge($passwordOptions, ['required' => false]);
        } else {
            $this->addNewUserFields($builder);
            $passwordOptions = array_merge($passwordOptions, ['required' => true, 'validation_groups' => ['create']]);
        }

        $builder->add('plainPassword', 'repeated', $passwordOptions);
    }

    /**
     * @param FormBuilderInterface $builder
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function addEntityFields(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'namePrefix',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.account.accountuser.name_prefix.label'
                ]
            )
            ->add(
                'firstName',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.account.accountuser.first_name.label'
                ]
            )
            ->add(
                'middleName',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.account.accountuser.middle_name.label'
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
                'nameSuffix',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.account.accountuser.name_suffix.label'
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'required' => true,
                    'label' => 'orob2b.account.accountuser.email.label'
                ]
            )
            ->add(
                'account',
                AccountSelectType::NAME,
                [
                    'required' => false,
                    'label' => 'orob2b.account.accountuser.account.label'
                ]
            )
            ->add(
                'enabled',
                'checkbox',
                [
                    'required' => false,
                    'label' => 'orob2b.account.accountuser.enabled.label',
                ]
            )
            ->add(
                'birthday',
                'oro_date',
                [
                    'required' => false,
                    'label' => 'orob2b.account.accountuser.birthday.label',
                ]
            )
            ->add(
                'addresses',
                AddressCollectionType::NAME,
                [
                    'label'    => 'orob2b.account.accountuser.addresses.label',
                    'type'     => AccountUserTypedAddressType::NAME,
                    'required' => false,
                    'options'  => [
                        'data_class'  => $this->addressClass,
                        'single_form' => false
                    ]
                ]
            )
        ;

        if ($this->securityFacade->isGranted('orob2b_account_account_user_role_view')) {
            $builder->add(
                'roles',
                'entity',
                [
                    'property_path' => 'roles',
                    'label' => 'orob2b.account.accountuser.roles.label',
                    'class' => $this->roleClass,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'required' => true
                ]
            );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addNewUserFields(FormBuilderInterface $builder)
    {
        $builder
            ->add(
                'passwordGenerate',
                'checkbox',
                [
                    'required' => false,
                    'label'    => 'orob2b.account.accountuser.password_generate.label',
                    'mapped'   => false
                ]
            )
            ->add(
                'sendEmail',
                'checkbox',
                [
                    'required' => false,
                    'label'    => 'orob2b.account.accountuser.send_email.label',
                    'mapped'   => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['data']);

        $resolver->setDefaults([
            'cascade_validation'   => true,
            'data_class'           => $this->dataClass,
            'intention'            => 'account_user',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            'ownership_disabled'   => true
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
