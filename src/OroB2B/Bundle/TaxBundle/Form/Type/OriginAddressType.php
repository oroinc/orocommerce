<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class OriginAddressType extends AbstractType
{
    const NAME = 'orob2b_tax_origin_address';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var AddressCountryAndRegionSubscriber
     */
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
                    'required' => true,
                    'label' => 'oro.address.region.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_region'],
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'postal_code',
                'text',
                [
                    'required' => true,
                    'label' => 'oro.address.postal_code.label',
                    'attr' => ['placeholder' => 'oro.address.postal_code.label'],
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
