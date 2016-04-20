<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

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
                'required' => false,
                'label' => 'orob2b.shipping.shipping_origin.country.label'
            ])
            ->add('region', 'oro_region', [
                'required' => false,
                'label' => 'orob2b.shipping.shipping_origin.region.label'
            ])
            ->add('postalCode', 'text', [
                'required' => false,
                'label' => 'orob2b.shipping.shipping_origin.postal_code.label'
            ])
            ->add('city', 'text', [
                'required' => false,
                'label' => 'orob2b.shipping.shipping_origin.city.label'
            ])
            ->add('street', 'text', [
                'required' => false,
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
        $resolver->setDefaults(
            array(
                'data_class' => 'OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin',
                'intention' => 'shipping_origin',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
