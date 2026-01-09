<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for configuring the origin address used in tax calculations.
 *
 * The origin address represents the location from which goods are shipped or services are provided.
 * This address is used in certain tax calculation scenarios, particularly for origin-based tax systems.
 * The form includes fields for country, region, postal code, and handles the 'Use Default' checkbox
 * for scope-specific configuration.
 */
class OriginAddressType extends AbstractType
{
    public const NAME = 'oro_tax_origin_address';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    protected $countryAndRegionSubscriber;

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

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->countryAndRegionSubscriber);
        $builder
            ->add(
                'country',
                CountryType::class,
                [
                    'required' => false,
                    'label' => 'oro.address.country.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_country'],
                ]
            )
            ->add(
                'region',
                RegionType::class,
                [
                    'required' => false,
                    'label' => 'oro.address.region.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_region'],
                ]
            )
            ->add(
                'postal_code',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'oro.address.postal_code.label',
                    StripTagsExtension::OPTION_NAME => true,
                    'attr' => ['placeholder' => 'oro.address.postal_code.label'],
                ]
            )
            ->add(
                'region_text',
                HiddenType::class,
                [
                    'required' => false,
                    'label' => 'oro.address.region_text.label',
                    'attr' => ['placeholder' => 'oro.address.region_text.label'],
                ]
            );
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $parent = $form->getParent();
        if (!$parent) {
            return;
        }

        if (!$parent->has('use_parent_scope_value')) {
            return;
        }

        $useParentScopeValue = $parent->get('use_parent_scope_value')->getData();
        foreach ($view->children as $child) {
            $child->vars['use_parent_scope_value'] = $useParentScopeValue;
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
