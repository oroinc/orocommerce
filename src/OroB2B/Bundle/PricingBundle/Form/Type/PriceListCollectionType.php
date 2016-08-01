<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Doctrine\Common\Collections\Criteria;

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
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

class PriceListCollectionType extends AbstractType
{
    const NAME = 'orob2b_pricing_price_list_collection';
    const DEFAULT_ORDER = Criteria::DESC;

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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
        foreach ($submitted as $index => $item) {
            if ($this->isEmpty($item)) {
                $event->getForm()->remove($index);
            } else {
                $data[$index] = $item;
            }
        }

        $data = $this->reorderData($data, $event->getForm());

        $event->setData($data);
    }

    /**
     * Change data target when price lists swapped to avoid unique constraint failures
     *
     * @param array $submitted
     * @param FormInterface $form
     * @return array
     */
    protected function reorderData(array $submitted, FormInterface $form)
    {
        foreach ($form->all() as $child) {
            /** @var BasePriceListRelation $relation */
            $relation = $child->getData();
            if (!$relation) {
                continue;
            }
            $name = $child->getName();

            foreach ($submitted as $index => $item) {
                $id = (int)$item[PriceListSelectWithPriorityType::PRICE_LIST_FIELD];
                if ($relation->getPriceList()->getId() === $id) {
                    $temp = $submitted[$name];
                    $submitted[$name] = $submitted[$index];
                    $submitted[$index] = $temp;
                }
            }
        }

        return $submitted;
    }

    /**
     * @param PriceListAwareInterface|array $item
     * @return bool
     */
    protected function isEmpty($item)
    {
        return is_array($item)
            && !$item[PriceListSelectWithPriorityType::PRICE_LIST_FIELD]
            && !$item[PriceListSelectWithPriorityType::PRIORITY_FIELD];
    }
}
