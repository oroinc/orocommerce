<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class ShippingOriginType extends AbstractType
{
    const NAME = 'oro_shipping_origin';

    /** @var string */
    protected $dataClass;

    /** @var AddressCountryAndRegionSubscriber */
    protected $countryAndRegionSubscriber;

    /**
     * @param AddressCountryAndRegionSubscriber $eventListener
     */
    public function __construct(AddressCountryAndRegionSubscriber $eventListener)
    {
        $this->countryAndRegionSubscriber = $eventListener;
    }

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
        $builder->addEventSubscriber($this->countryAndRegionSubscriber);
        $builder
            ->add(
                'country',
                CountryType::class,
                [
                    'label' => 'oro.shipping.shipping_origin.country.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_country']
                ]
            )
            ->add(
                'region',
                RegionType::class,
                [
                    'label' => 'oro.shipping.shipping_origin.region.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_region']
                ]
            )
            ->add(
                'postalCode',
                TextType::class,
                [
                    'label' => 'oro.shipping.shipping_origin.postal_code.label',
                    'attr' => ['placeholder' => 'oro.shipping.shipping_origin.postal_code.label']
                ]
            )
            ->add(
                'city',
                TextType::class,
                [
                    'label' => 'oro.shipping.shipping_origin.city.label',
                    'attr' => ['placeholder' => 'oro.shipping.shipping_origin.city.label']
                ]
            )
            ->add(
                'street',
                TextType::class,
                [
                    'label' => 'oro.shipping.shipping_origin.street.label',
                    'attr' => ['placeholder' => 'oro.shipping.shipping_origin.street.label']
                ]
            )
            ->add(
                'street2',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.shipping.shipping_origin.street2.label',
                    'attr' => ['placeholder' => 'oro.shipping.shipping_origin.street2.label']
                ]
            )
            ->add(
                'region_text',
                HiddenType::class,
                [
                    'required' => false,
                    'random_id' => true,
                    'label' => 'oro.shipping.shipping_origin.region_text.label',
                    'attr' => ['placeholder' => 'oro.shipping.shipping_origin.region_text.label']
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'csrf_token_id' => 'shipping_origin'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
