<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAwareInterface;

class PriceListCollectionType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_collection';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
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
                'constraints' => [new UniquePriceList()],
                'required' => false,
                'render_as_widget' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['render_as_widget'] = $options['render_as_widget'];
    }

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

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = [];
        $submitted = $event->getData() ?: [];
        foreach ($submitted as $i => $item) {
            if ($this->isEmpty($item)) {
                $event->getForm()->remove($i);
            } else {
                $data[$i] = $item;
            }
        }
        $event->setData($data);
    }

    /**
     * @param PriceListAwareInterface|array $item
     * @return bool
     */
    protected function isEmpty($item)
    {
        return ($item instanceof PriceListAwareInterface && !$item->getPriceList() && !$item->getPriority())
            || (is_array($item) && !$item['priceList'] && !$item['priority']);
    }
}
