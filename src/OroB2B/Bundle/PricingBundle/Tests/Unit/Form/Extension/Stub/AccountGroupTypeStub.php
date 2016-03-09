<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;

class AccountGroupTypeStub extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return AccountGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', ['label' => 'orob2b.account_group.name.label']);
    }
}
