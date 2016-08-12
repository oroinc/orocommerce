<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination;

class ShippingRuleDestinationType extends AbstractType
{
    const NAME = 'orob2b_shipping_rule_destination';

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
        $builder->add('country', 'oro_country', ['required' => true, 'label' => 'oro.address.country.label'])
            ->add('region', 'oro_region', ['required' => false, 'label' => 'oro.address.region.label'])
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
