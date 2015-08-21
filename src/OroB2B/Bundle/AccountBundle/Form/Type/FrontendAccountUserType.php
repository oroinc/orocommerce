<?php
namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FrontendAccountUserType extends AbstractType
{
    const NAME = 'orob2b_account_frontend_account_user';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $roleClass;

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
     * {@inheritdoc}
     */
    public function getParent()
    {
        return AccountUserType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($builder->has('roles')) {
            $builder->remove('roles');
        }
        //TODO: uncomment after marge with BB-777
//        if ($this->securityFacade->isGranted('orob2b_account_frontend_account_user_role_view')) {
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
//        }
    }
}

