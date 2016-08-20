<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
                    'required' => false,
                    'label' => 'oro.address.country.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_country'],
                ]
            )
            ->add(
                'region',
                'oro_region',
                [
                    'required' => false,
                    'label' => 'oro.address.region.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_region'],
                ]
            )
            ->add(
                'postal_code',
                'text',
                [
                    'required' => false,
                    'label' => 'oro.address.postal_code.label',
                    'attr' => ['placeholder' => 'oro.address.postal_code.label'],
                ]
            )
            ->add(
                'region_text',
                'hidden',
                [
                    'required' => false,
                    'label' => 'oro.address.region_text.label',
                    'attr' => ['placeholder' => 'oro.address.region_text.label'],
                ]
            );
    }

    /** {@inheritdoc} */
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
