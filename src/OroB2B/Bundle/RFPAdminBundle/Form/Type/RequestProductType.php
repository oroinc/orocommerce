<?php

namespace OroB2B\Bundle\RFPAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;

class RequestProductType extends AbstractType
{
    const NAME = 'orob2b_rfp_admin_request_product';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', null, [
                'required'  => true,
                'label'     => 'orob2b.product.entity_label',
            ])
            ->add(
                'requestProductItems',
                RequestProductItemCollectionType::NAME,
                [
                    'label'     => 'orob2b.rfpadmin.requestproductitem.entity_plural_label',
                    'add_label' => 'orob2b.rfpadmin.requestproductitem.add_label',
                    'required'  => false
                ]
            )
            ->add('comment', 'textarea', [
                'required'  => false,
                'label'     => 'orob2b.rfpadmin.requestproduct.comment.label',
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => 'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct',
            'intention'     => 'rfp_admin_request_product',
            'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /* @var $requestProduct RequestProduct */
        $requestProduct = $event->getData();
        $form = $event->getForm();
        if ($requestProduct && null !== $requestProduct->getId()) {
            $product = $requestProduct->getProduct();
            if (!$product) {
                $emptyValueTitle = $this->translator->trans(
                    'orob2b.rfpadmin.message.requestproductitem.unit.removed',
                    [
                        '{title}' => $requestProduct->getProductSku(),
                    ]
                );
                $form->add(
                    'product',
                    null,
                    [
                        'required'      => true,
                        'label'         => 'orob2b.product.entity_label',
                        'empty_value'   => $emptyValueTitle,
                    ]
                );
            }
        }
    }
}
