<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Oro\Bundle\CustomerBundle\Form\Type\CustomerSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserMultiSelectType;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\UserBundle\Form\Type\UserMultiSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestType extends AbstractType
{
    const NAME = 'oro_rfp_request';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'oro.rfp.request.first_name.label',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'oro.rfp.request.last_name.label',
            ])
            ->add('email', TextType::class, [
                'label' => 'oro.rfp.request.email.label',
            ])
            ->add('phone', TextType::class, [
                'label' => 'oro.rfp.request.phone.label',
                'required' => false,
            ])
            ->add('company', TextType::class, [
                'label' => 'oro.rfp.request.company.label',
            ])
            ->add('role', TextType::class, [
                'label' => 'oro.rfp.request.role.label',
                'required' => false,
            ])
            ->add('customerUser', CustomerUserSelectType::class, [
                'label' => 'oro.rfp.request.customer_user.label',
                'required' => false,
            ])
            ->add('customer', CustomerSelectType::class, [
                'label' => 'oro.rfp.request.customer.label',
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'oro.rfp.request.note.label',
                'required' => false,
            ])
            ->add('poNumber', TextType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.po_number.label'
            ])
            ->add('shipUntil', OroDateType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.ship_until.label'
            ])
            ->add('requestProducts', RequestProductCollectionType::class, [
                'label'     => 'oro.rfp.requestproduct.entity_plural_label',
                'add_label' => 'oro.rfp.requestproduct.add_label',
                'entry_options' => [
                    'compact_units' => true,
                ],
            ])
            ->add('assignedUsers', UserMultiSelectType::class, [
                'label' => 'oro.rfp.request.assigned_users.label',
            ])
            ->add('assignedCustomerUsers', CustomerUserMultiSelectType::class, [
                'label' => 'oro.rfp.request.assigned_customer_users.label',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'           => $this->dataClass,
            'csrf_token_id'        => 'rfp_request',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
