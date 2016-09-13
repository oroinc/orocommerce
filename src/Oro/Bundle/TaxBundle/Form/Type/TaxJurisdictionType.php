<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

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

    /**
     * @param AddressCountryAndRegionSubscriber $countryAndRegionSubscriber
     */
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
            ->add('code', 'text', [
                'label' => 'oro.tax.taxjurisdiction.code.label',
                'required' => true
            ])
            ->add('description', 'textarea', [
                'label' => 'oro.tax.taxjurisdiction.description.label',
                'required' => false
            ])
            ->add('country', 'oro_country', [
                'required' => true,
                'label' => 'oro.tax.taxjurisdiction.country.label'
            ])
            ->add('region', 'oro_region', [
                'required' => false,
                'label' => 'oro.tax.taxjurisdiction.region.label'
            ])
            ->add('region_text', 'hidden', [
                'required' => false,
                'random_id' => true,
                'label' => 'oro.tax.taxjurisdiction.region_text.label'
            ])
            ->add('zipCodes', ZipCodeCollectionType::NAME, [
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
