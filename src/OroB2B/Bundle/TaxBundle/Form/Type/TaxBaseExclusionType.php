<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TaxBaseExclusionType extends AbstractType
{
    const NAME = 'orob2b_tax_base_exclusion';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    private $countryAndRegionSubscriber;

    /**
     * @param AddressCountryAndRegionSubscriber $countryAndRegionSubscriber
     */
    public function __construct(AddressCountryAndRegionSubscriber $countryAndRegionSubscriber)
    {
        $this->countryAndRegionSubscriber = $countryAndRegionSubscriber;
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
            ->add(
                'country',
                'oro_country',
                [
                    'required' => true,
                    'label' => 'oro.address.country.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_country'],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'region',
                'oro_region',
                [
                    'required' => false,
                    'label' => 'oro.address.region.label',
                ]
            )
            ->add(
                'option',
                'choice',
                [
                    'required' => true,
                    'choices' => [
                        TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN =>
                            'orob2b.tax.system_configuration.fields.use_as_base.shipping_origin.label',
                        TaxationSettingsProvider::USE_AS_BASE_DESTINATION =>
                            'orob2b.tax.system_configuration.fields.use_as_base.destination.label',
                    ],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'region_text',
                'hidden',
                [
                    'required' => true,
                    'label' => 'oro.address.region_text.label',
                    'attr' => ['placeholder' => 'oro.address.region_text.label'],
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
            ]
        );
    }

    /**
     * @return string
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
