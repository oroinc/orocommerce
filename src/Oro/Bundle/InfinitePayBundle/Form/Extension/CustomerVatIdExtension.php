<?php

namespace Oro\Bundle\InfinitePayBundle\Form\Extension;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerType;
use Oro\Bundle\InfinitePayBundle\Form\Type\VatIdType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerVatIdExtension extends AbstractTypeExtension
{
    public function getExtendedType()
    {
        CustomerType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'vatId',
                VatIdType::NAME,
                [
                    'required' => false,
                    'mapped' => false,
                    'label' => 'oro.infinite_pay.form.vat_id.label',
                    'create_form_route' => null,
                ]
            );
    }
}
