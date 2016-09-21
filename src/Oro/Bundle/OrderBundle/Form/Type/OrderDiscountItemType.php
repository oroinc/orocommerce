<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Range;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;

class OrderDiscountItemType extends AbstractType
{
    const NAME = 'oro_order_discount_item';

    /**
     * @var string
     */
    protected $dataClass;

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['currency', 'total']);
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'order_discount_item',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [],
            ]
        );
        $resolver->setAllowedTypes('page_component_options', 'array');
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('currency', ['null', 'string']);

        $resolver->setDefault(
            'page_component_options',
            [
                'view' => 'oroorder/js/app/views/discount-item-view',
                'percentTypeValue' => OrderDiscount::TYPE_PERCENT,
                'totalType' => LineItemSubtotalProvider::TYPE,
                'discountType' => DiscountSubtotalProvider::TYPE,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'value',
                'text',
                [
                    'required' => true,
                    'mapped' => false
                ]
            )
            ->add(
                'type',
                'choice',
                [
                    'choices' => [
                        OrderDiscount::TYPE_AMOUNT => $options['currency'],
                        OrderDiscount::TYPE_PERCENT => 'oro.order.orderdiscountitem.types.percent',
                    ]
                ]
            )
            ->add(
                'description',
                'text',
                [
                    'error_bubbling' => false,
                    'required' => false,
                ]
            )
            ->add('percent', 'hidden')
            ->add(
                'amount',
                'hidden',
                [
                    'constraints' => [
                        //range should be used, because this type also is implemented with JS
                        new Range(
                            [
                                'min' => PHP_INT_MAX * (-1), //use some big negative number
                                'max' => $options['total'],
                                'maxMessage' => 'oro.order.discounts.item.error.label'
                            ]
                        ),
                        new Type(['type' => 'numeric'])
                    ]
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (array_key_exists('page_component', $options)) {
            $view->vars['page_component'] = $options['page_component'];
        } else {
            $view->vars['page_component'] = null;
        }

        if (array_key_exists('page_component_options', $options)) {
            $view->vars['page_component_options'] = $options['page_component_options'];
        }
        $view->vars['page_component_options']['currency'] = $options['currency'];
    }

    /**
     * POST_SET_DATA event handler
     *
     * @param FormEvent $event
     */
    public function postSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if ($data && $form->has('value')) {
            $form->get('value')->setData((double)$data->getValue());
        }
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
