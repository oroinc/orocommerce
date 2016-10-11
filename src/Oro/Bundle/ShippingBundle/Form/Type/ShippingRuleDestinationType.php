<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;

class ShippingRuleDestinationType extends AbstractType
{
    const NAME = 'oro_shipping_rule_destination';

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    protected $subscriber;

    /**
     * @param AddressCountryAndRegionSubscriber $subscriber
     */
    public function __construct(AddressCountryAndRegionSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);
        $builder->add('country', CountryType::class, ['required' => true, 'label' => 'oro.address.country.label'])
            ->add('region', RegionType::class, ['required' => false, 'label' => 'oro.address.region.label'])
            ->add(
                'region_text',
                HiddenType::class,
                ['required' => false, 'random_id' => true, 'label' => 'oro.address.region_text.label']
            )
            ->add('postalCode', TextType::class, ['required' => false, 'label' => 'oro.address.postal_code.label']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingRuleDestination::class,
            'region_route' => 'oro_api_country_get_regions',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!empty($options['region_route'])) {
            $view->vars['region_route'] = $options['region_route'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
