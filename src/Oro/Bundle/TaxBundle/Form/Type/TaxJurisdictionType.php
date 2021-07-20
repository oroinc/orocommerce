<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxJurisdictionType extends AbstractType
{
    const NAME = 'oro_tax_jurisdiction_type';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    protected $countryAndRegionSubscriber;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    public function __construct(AddressCountryAndRegionSubscriber $countryAndRegionSubscriber)
    {
        $this->countryAndRegionSubscriber = $countryAndRegionSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->countryAndRegionSubscriber);

        $builder
            ->add('code', TextType::class, [
                'label' => 'oro.tax.taxjurisdiction.code.label',
                StripTagsExtension::OPTION_NAME => true,
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => 'oro.tax.taxjurisdiction.description.label',
                'required' => false
            ])
            ->add('country', CountryType::class, [
                'required' => true,
                'label' => 'oro.tax.taxjurisdiction.country.label'
            ])
            ->add('region', RegionType::class, [
                'required' => false,
                'label' => 'oro.tax.taxjurisdiction.region.label'
            ])
            ->add('region_text', HiddenType::class, [
                'required' => false,
                'random_id' => true,
                'label' => 'oro.tax.taxjurisdiction.region_text.label'
            ])
            ->add('zipCodes', ZipCodeCollectionType::class, [
                'required' => false,
                'label' => 'oro.tax.taxjurisdiction.zip_codes.label',
                'tooltip'  => 'oro.tax.form.tooltip.zip_codes'
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
        ]);
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
