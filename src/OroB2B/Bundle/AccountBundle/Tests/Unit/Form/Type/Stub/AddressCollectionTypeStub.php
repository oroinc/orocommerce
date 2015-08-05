<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\AddressBundle\Form\Type\AddressCollectionType;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountTypedAddressType;

class AddressCollectionTypeStub extends AddressCollectionType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'type'     => AccountTypedAddressType::NAME,
            'options'  => ['data_class' => 'OroB2B\Bundle\AccountBundle\Entity\AccountAddress'],
            'multiple' => true,
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
