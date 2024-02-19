<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\CustomerBundle\Form\Type\Frontend\CustomerUserMultiSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\RFPBundle\Entity\Request;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents an RFP request.
 */
class RequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                'required' => false,
                'label' => 'oro.rfp.request.phone.label',
            ])
            ->add('company', TextType::class, [
                'label' => 'oro.rfp.request.company.label',
            ])
            ->add('role', TextType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.role.label',
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.note.label',
            ])
            ->add('poNumber', TextType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.po_number.label',
            ])
            ->add('shipUntil', OroDateType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.ship_until.label',
            ])
            ->add('requestProducts', RequestProductCollectionType::class)
            ->add('assignedCustomerUsers', CustomerUserMultiSelectType::class, [
                'label' => 'oro.frontend.rfp.request.assigned_customer_users.label',
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * Remove empty requestProducts
     */
    public function onPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        if (!array_key_exists('requestProducts', $data)) {
            return;
        }

        foreach ($data['requestProducts'] as $key => $requestProduct) {
            if (empty($requestProduct['product'])) {
                unset($data['requestProducts'][$key]);
            }
        }

        $event->setData($data);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Request::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'oro_rfp_frontend_request';
    }
}
