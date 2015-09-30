<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountSelectType;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserSelectType;

class RequestType extends AbstractType
{
    const NAME = 'orob2b_rfp_request';

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
            ->add('firstName', 'text', [
                'label' => 'orob2b.rfp.request.first_name.label',
            ])
            ->add('lastName', 'text', [
                'label' => 'orob2b.rfp.request.last_name.label',
            ])
            ->add('email', 'text', [
                'label' => 'orob2b.rfp.request.email.label',
            ])
            ->add('phone', 'text', [
                'label' => 'orob2b.rfp.request.phone.label',
                'required' => false,
            ])
            ->add('company', 'text', [
                'label' => 'orob2b.rfp.request.company.label',
            ])
            ->add('role', 'text', [
                'label' => 'orob2b.rfp.request.role.label',
                'required' => false,
            ])
            ->add('accountUser', AccountUserSelectType::NAME, [
                'label' => 'orob2b.rfp.request.account_user.label',
                'required' => false,
            ])
            ->add('account', AccountSelectType::NAME, [
                'label' => 'orob2b.rfp.request.account.label',
                'required' => false,
            ])
            ->add('status', RequestStatusSelectType::NAME, [
                'label' => 'orob2b.rfp.request.status.label',
            ])
            ->add('body', 'textarea', [
                'label' => 'orob2b.rfp.request.body.label',
                'required' => false,
            ])
            ->add('requestProducts', RequestProductCollectionType::NAME, [
                'label'     => 'orob2b.rfp.requestproduct.entity_plural_label',
                'add_label' => 'orob2b.rfp.requestproduct.add_label',
                'options' => [
                    'compact_units' => true,
                ],
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
            'intention'            => 'rfp_request',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
