<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class ShippingOriginType extends AbstractType
{
    const NAME = 'orob2b_shipping_origin';

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    private $countryAndRegionSubscriber;

    /**
     * @param AddressCountryAndRegionSubscriber $eventListener
     */
    public function __construct(AddressCountryAndRegionSubscriber $eventListener)
    {
        $this->countryAndRegionSubscriber = $eventListener;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country', 'oro_country', [
                'label' => 'orob2b.shipping.shipping_origin.country.label'
            ])
            ->add('region', 'oro_region', [
                'label' => 'orob2b.shipping.shipping_origin.region.label'
            ])
            ->add('postal_code', 'text', [
                'label' => 'orob2b.shipping.shipping_origin.postal_code.label'
            ])
            ->add('city', 'text', [
                'label' => 'orob2b.shipping.shipping_origin.city.label'
            ])
            ->add('street', 'text', [
                'label' => 'orob2b.shipping.shipping_origin.street.label'
            ])
            ->add('street2', 'text', [
                'required' => false,
                'label' => 'orob2b.shipping.shipping_origin.street2.label'
            ])
            ->add('region_text', 'hidden', [
                'required' => false,
                'random_id' => true,
                'label' => 'orob2b.shipping.shipping_origin.region_text.label'
            ])
            ->addEventSubscriber($this->countryAndRegionSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin',
            'intention' => 'shipping_origin',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
        ]);
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $parent = $form->getParent();
        if (!$parent) {
            return;
        }

        if (!$parent->has('use_parent_scope_value')) {
            return;
        }

        $useParentScopeValue = $parent->get('use_parent_scope_value')->getData();
        foreach ($view->children as $child) {
            $child->vars['use_parent_scope_value'] = $useParentScopeValue;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
