<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The form type for {@see \Oro\Bundle\TaxBundle\Model\TaxBaseExclusion}.
 */
class TaxBaseExclusionType extends AbstractType
{
    const NAME = 'oro_tax_base_exclusion';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    private $countryAndRegionSubscriber;

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->countryAndRegionSubscriber);
        $builder
            ->add(
                'country',
                CountryType::class,
                [
                    'required' => true,
                    'label' => 'oro.address.country.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_country'],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'region',
                RegionType::class,
                [
                    'required' => false,
                    'label' => 'oro.address.region.label',
                ]
            )
            ->add(
                'option',
                ChoiceType::class,
                [
                    'required' => true,
                    'choices' => [
                        'oro.tax.system_configuration.fields.use_as_base.origin.label' =>
                            TaxationSettingsProvider::USE_AS_BASE_ORIGIN,
                        'oro.tax.system_configuration.fields.use_as_base.destination.label' =>
                            TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                    ],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'region_text',
                HiddenType::class,
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
