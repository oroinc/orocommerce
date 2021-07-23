<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\CustomerBundle\Form\Type\Frontend\CustomerUserMultiSelectType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestType extends AbstractType
{
    const NAME = 'oro_rfp_frontend_request';

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
                'label' => 'oro.rfp.request.first_name.label'
            ])
            ->add('lastName', TextType::class, [
                'label' => 'oro.rfp.request.last_name.label'
            ])
            ->add('email', TextType::class, [
                'label' => 'oro.rfp.request.email.label'
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.phone.label'
            ])
            ->add('company', TextType::class, [
                'label' => 'oro.rfp.request.company.label'
            ])
            ->add('role', TextType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.role.label'
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'label' => 'oro.rfp.request.note.label'
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
                'entry_options' => [
                    'compact_units' => true,
                ],
            ])
            ->add('assignedCustomerUsers', CustomerUserMultiSelectType::class, [
                'label' => 'oro.frontend.rfp.request.assigned_customer_users.label',
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * Remove empty requestProducts
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (!array_key_exists('requestProducts', $data)) {
            return;
        }
        foreach ($data['requestProducts'] as $key => $requestProduct) {
            if (!$requestProduct['product']) {
                unset($data['requestProducts'][$key]);
            }
        }
        $event->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass
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
        return static::NAME;
    }
}
