<?php

namespace Oro\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRuleDestination;
use Oro\Bundle\PaymentBundle\Form\DataTransformer\DestinationPostalCodeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentMethodsConfigsRuleDestinationType extends AbstractType
{
    const NAME = 'oro_payment_methods_configs_rule_destination';

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    protected $subscriber;

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
        $builder
            ->add('country', CountryType::class, ['required' => true, 'label' => 'oro.address.country.label'])
            ->add('region', RegionType::class, ['required' => false, 'label' => 'oro.address.region.label'])
            ->add(
                'region_text',
                HiddenType::class,
                ['required' => false, 'label' => 'oro.address.region_text.label']
            )
            ->add('postalCodes', TextType::class, [
                'required' => false,
                'label' => 'oro.payment.paymentmethodsconfigsruledestination.postal_codes.label',
                StripTagsExtension::OPTION_NAME => true,
            ])
        ;

        $builder->get('postalCodes')->addModelTransformer(new DestinationPostalCodeTransformer());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PaymentMethodsConfigsRuleDestination::class,
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
