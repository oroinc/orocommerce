<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\TaxBundle\Model\TaxBaseException;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class TaxBaseExceptionType extends AbstractType
{
    const NAME = 'orob2b_tax_base_exception';

    /**
     * @var string
     */
    protected $dataClass;

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
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->countryAndRegionSubscriber);
        $builder
            ->add('country', 'oro_country', array('required' => true, 'label' => 'oro.address.country.label'))
            ->add('region', 'oro_region', array('required' => false, 'label' => 'oro.address.region.label'))
            ->add(
                'option',
                'choice',
                [
                    'required' => true,
                    'choices' => [
                        TaxBaseException::USE_AS_BASE_SHIPPING_ORIGIN =>
                            'orob2b.tax.system_configuration.fields.use_as_base.shipping_origin.label',
                        TaxBaseException::USE_AS_BASE_DESTINATION =>
                            'orob2b.tax.system_configuration.fields.use_as_base.destination.label',
                    ]
                ]
            )
            ->add(
                'region_text',
                'hidden',
                [
                    'required' => false,
                    'mapped' => false,
                    'random_id' => true,
                    'label' => 'oro.address.region_text.label'
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
                'data_class' => $this->dataClass
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
