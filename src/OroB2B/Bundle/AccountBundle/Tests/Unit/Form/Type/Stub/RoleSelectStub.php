<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleSelectStub extends EntitySelectTypeStub
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'OroB2B\Bundle\AccountBundle\Entity\AccountUserRole',
            'expanded' => true
        ]);
    }
}
