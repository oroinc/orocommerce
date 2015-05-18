<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerGroupSelectType;

class CustomerGroupSelectTypeStub extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return CustomerGroupSelectType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'class' => 'OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup',
            'property' => 'name'
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
