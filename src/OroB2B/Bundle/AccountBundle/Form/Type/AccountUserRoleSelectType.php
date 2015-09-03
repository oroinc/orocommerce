<?php
namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountUserRoleSelectType extends AbstractType
{
    const NAME = 'orob2b_account_account_user_role_select';

    /**
     * @var string
     */
    protected $roleClass;

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
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => $this->roleClass,
            'property' => 'label',
            'multiple' => true,
            'expanded' => true,
            'required' => true
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }
}
