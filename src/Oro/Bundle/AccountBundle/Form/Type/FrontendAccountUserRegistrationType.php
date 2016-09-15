<?php

namespace Oro\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

class FrontendAccountUserRegistrationType extends AbstractType
{
    const NAME = 'oro_account_frontend_account_user_register';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param ConfigManager $configManager
     * @param UserManager $userManager
     */
    public function __construct(ConfigManager $configManager, UserManager $userManager)
    {
        $this->configManager = $configManager;
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'companyName',
                'text',
                [
                    'required' => true,
                    'mapped' => false,
                    'label' => 'oro.account.accountuser.profile.company_name',
                    'constraints' => [new Assert\NotBlank()]
                ]
            )
            ->add(
                'firstName',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.account.accountuser.first_name.label'
                ]
            )
            ->add(
                'lastName',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.account.accountuser.last_name.label'
                ]
            )
            ->add(
                'email',
                'email',
                [
                    'required' => true,
                    'label' => 'oro.account.accountuser.email.label'
                ]
            );

        $builder->add(
            'plainPassword',
            'repeated',
            [
                'type' => 'password',
                'first_options' => ['label' => 'oro.account.accountuser.password.label'],
                'second_options' => ['label' => 'oro.account.accountuser.password_confirmation.label'],
                'invalid_message' => 'oro.account.message.password_mismatch',
                'required' => true,
                'validation_groups' => ['create']
            ]
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                /** @var AccountUser $accountUser */
                $accountUser = $event->getData();

                if (!$accountUser->getOwner()) {
                    $userId = $this->configManager->get('oro_b2b_account.default_account_owner');

                    /** @var User $user */
                    $user = $this->userManager->getRepository()->find($userId);

                    if ($user) {
                        $accountUser->setOwner($user);
                    }
                }
            }
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var AccountUser $accountUser */
                $accountUser = $event->getData();
                if (!$accountUser->getAccount()) {
                    $form = $event->getForm();
                    $companyName = $form->get('companyName')->getData();
                    $accountUser->createAccount($companyName);
                }
            },
            10
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
     * @return FrontendAccountUserType
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;

        return $this;
    }
}
