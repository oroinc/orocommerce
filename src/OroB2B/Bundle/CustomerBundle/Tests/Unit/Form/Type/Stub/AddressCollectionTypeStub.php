<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\AddressBundle\Form\Type\AddressCollectionType;
use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerTypedAddressType;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AddressCollectionTypeStub extends AddressCollectionType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'type'     => CustomerTypedAddressType::NAME,
            'options'  => ['data_class' => 'OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress']
        ]);

        parent::setDefaultOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'test_address_entity';
    }
}
