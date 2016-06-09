<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class ProductAttributePriceCollectionType extends FormType
{
    const NAME = 'orob2b_pricing_price_list_collection';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
//        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'website' => null,
                'type' => PriceListSelectWithPriorityType::NAME,
                'mapped' => false,
                'label' => false,
                'handle_primary' => false,
//                'constraints' => [new UniquePriceList()],
                'required' => false,
                'render_as_widget' => false,
            ]
        );
    }

//    /**
//     * {@inheritdoc}
//     */
//    public function finishView(FormView $view, FormInterface $form, array $options)
//    {
//        $view->vars['render_as_widget'] = $options['render_as_widget'];
//    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
