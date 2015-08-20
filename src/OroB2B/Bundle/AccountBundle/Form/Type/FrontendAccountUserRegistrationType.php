<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class FrontendAccountUserRegistrationType extends AbstractType
{
    const NAME = 'orob2b_account_frontend_account_user_register';

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
