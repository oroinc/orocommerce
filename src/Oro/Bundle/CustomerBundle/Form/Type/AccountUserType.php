<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\Type\AddressCollectionType;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;

class AccountUserType extends AbstractType
{
    const NAME = 'oro_account_account_user';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $addressClass;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

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
            'type' => 'password',
            'required' => false,
            'first_options' => ['label' => 'oro.customer.customeruser.password.label'],
            'second_options' => ['label' => 'oro.customer.customeruser.password_confirmation.label'],
            'invalid_message' => 'oro.customer.message.password_mismatch',
        ];

        if ($data instanceof CustomerUser && $data->getId()) {
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
                    'label' => 'oro.customer.customeruser.name_prefix.label'
                ]
            )
            ->add(
                'firstName',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.customer.customeruser.first_name.label'
                ]
            )
            ->add(
                'middleName',
                'text',
                [
                    'required' => false,
                    'label' => 'oro.customer.customeruser.middle_name.label'
                ]
            )
            ->add(
                'lastName',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.customer.customeruser.last_name.label'
                ]
            )
            ->add(
                'nameSuffix',
                'text',
                [
                    'required' => false,
                    'label' => 'oro.customer.customeruser.name_suffix.label'
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'required' => true,
                    'label' => 'oro.customer.customeruser.email.label'
                ]
            )
            ->add(
                'account',
                AccountSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'oro.customer.customeruser.account.label'
                ]
            )
            ->add(
                'enabled',
                'checkbox',
                [
                    'required' => false,
                    'label' => 'oro.customer.customeruser.enabled.label',
                ]
            )
            ->add(
                'birthday',
                'oro_date',
                [
                    'required' => false,
                    'label' => 'oro.customer.customeruser.birthday.label',
                ]
            )
            ->add(
                'addresses',
                AddressCollectionType::NAME,
                [
                    'label' => 'oro.customer.customeruser.addresses.label',
                    'type' => AccountUserTypedAddressType::NAME,
                    'required' => false,
                    'options' => [
                        'data_class' => $this->addressClass,
                        'single_form' => false
                    ]
                ]
            )
            ->add(
                'salesRepresentatives',
                UserMultiSelectType::NAME,
                [
                    'label' => 'oro.customer.account.sales_representatives.label',
                ]
            );

        if ($this->securityFacade->isGranted('oro_account_customer_user_role_view')) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
            $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
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
                    'label' => 'oro.customer.customeruser.password_generate.label',
                    'mapped' => false
                ]
            )
            ->add(
                'sendEmail',
                'checkbox',
                [
                    'required' => false,
                    'label' => 'oro.customer.customeruser.send_email.label',
                    'mapped' => false
                ]
            );
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var CustomerUser $data */
        $data = $event->getData();
        $data->setOrganization($this->securityFacade->getOrganization());

        $form->add(
            'roles',
            CustomerUserRoleSelectType::NAME,
            [
                'query_builder' => function (CustomerUserRoleRepository $repository) use ($data) {
                    return $repository->getAvailableRolesByAccountUserQueryBuilder(
                        $data->getOrganization(),
                        $data->getAccount()
                    );
                }
            ]
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();

        $form->add(
            'roles',
            CustomerUserRoleSelectType::NAME,
            [
                'query_builder' => function (CustomerUserRoleRepository $repository) use ($data) {
                    $account = null;
                    if (array_key_exists('account', $data)) {
                        $account = $data['account'];
                    }

                    return $repository->getAvailableRolesByAccountUserQueryBuilder(
                        $this->securityFacade->getOrganization(),
                        $account
                    );
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);

        $resolver->setDefaults([
            'cascade_validation' => true,
            'data_class' => $this->dataClass,
            'intention' => 'account_user',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            'ownership_disabled' => true,
        ]);
    }

    /**
     * @return string
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
