<?php

namespace OroB2B\Bundle\UserAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FrontendRolesType extends AbstractType
{
    const NAME = 'orob2b_frontend_roles';

    /**
     * @var array
     */
    protected $roles = [];

    /**
     * @param array $frontendRoles
     */
    public function __construct(array $frontendRoles)
    {
        $this->roles = $frontendRoles;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = [];
        foreach ($this->roles as $key => $value) {
            $choices[$key] = $value['label'];
        }

        $resolver->setDefaults([
            'choices' => $choices,
            'multiple' => true,
            'expanded' => true
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
