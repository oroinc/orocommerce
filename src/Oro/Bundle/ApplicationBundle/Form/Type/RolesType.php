<?php

namespace Oro\Bundle\ApplicationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RolesType extends AbstractType
{
    const NAME = 'orob2b_roles';

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

    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return self::NAME;
    }
}
