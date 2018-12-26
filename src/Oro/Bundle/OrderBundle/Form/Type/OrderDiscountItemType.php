<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroHiddenNumberType;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Form type for order discount item - absolute or relative (percent) discount.
 */
class OrderDiscountItemType extends AbstractType
{
    const NAME = 'oro_order_discount_item';
    const VALIDATION_GROUP = 'OrderDiscountItemType';

    /**
     * @var TotalHelper
     */
    protected $totalHelper;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param TotalHelper $totalHelper
     */
    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['currency', 'total']);
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'csrf_token_id' => 'order_discount_item',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [],
                'validation_groups' => [self::VALIDATION_GROUP]
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
                TextType::class,
                [
                    'required' => true,
                    'mapped' => false,
                    'label' => 'oro.order.orderdiscount.value.label'
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => [
                        $options['currency'] => OrderDiscount::TYPE_AMOUNT,
                        'oro.order.order_discount.types.percent' => OrderDiscount::TYPE_PERCENT,
                    ]
                ]
            )
            ->add(
                'description',
                TextType::class,
                [
                    'error_bubbling' => false,
                    'required' => false,
                ]
            )
            ->add('percent', OroHiddenNumberType::class)
            ->add(
                'amount',
                OroHiddenNumberType::class,
                [
                    'constraints' => [
                        //range should be used, because this type also is implemented with JS
                        new Range(
                            [
                                'min' => 0,
                                'max' => $options['total'],
                                'maxMessage' => 'oro.order.discounts.item.error.label',
                                'groups' => [self::VALIDATION_GROUP]
                            ]
                        ),
                    ]
                ]
            );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'submit']);
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
     * SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        /** @var OrderDiscount $data */
        $data = $event->getData();
        if ($data->getOrder()) {
            $this->totalHelper->fillDiscounts($data->getOrder());
        }
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
        if ($data && $form->has('value') && null !== $data->getValue()) {
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
