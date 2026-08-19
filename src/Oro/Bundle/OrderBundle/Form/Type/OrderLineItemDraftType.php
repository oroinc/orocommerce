<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderLineItemTierPricesProvider;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents form type for order line item draft.
 */
final class OrderLineItemDraftType extends AbstractType
{
    public function __construct(
        private readonly OrderLineItemTierPricesProvider $tierPricesProvider,
        private readonly EventSubscriberInterface $orderLineItemDraftDrySubmitListener,
        private readonly EventSubscriberInterface $orderLineItemDraftChecksumListener
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('drySubmitTrigger', HiddenType::class, ['mapped' => false])
            ->add('isFreeForm', HiddenType::class, [
                'setter' => static function (OrderLineItem $orderLineItem, $value): void {
                    // Convert string "0"/"1" to bool for strict_types compatibility
                    $orderLineItem->setIsFreeForm((bool)(int)$value);
                },
            ])
            ->add('product', OrderLineItemDraftProductType::class)
            ->add('quantity', QuantityType::class, ['required' => true, 'default_data' => 1])
            ->add('productUnit', ProductUnitSelectionType::class, ['required' => true, 'sell' => true])
            ->add(
                'price',
                OrderPriceType::class,
                [
                    'required' => true,
                    'error_bubbling' => true,
                    'hide_currency' => true,
                    'by_reference' => false,
                ]
            )
            ->add('priceType', HiddenType::class, ['data' => PriceTypeAwareInterface::PRICE_TYPE_UNIT])
            ->add('shipBy', OroDateType::class, ['required' => false])
            ->add('comment', TextareaType::class, ['required' => false]);

        $builder
            ->get('isFreeForm')
            ->addEventListener(FormEvents::POST_SET_DATA, $this->addFreeFormProductOnIsFreeFormPostSetData(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->addFreeFormProductOnIsFreeFormPostSubmit(...));

        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->fillFreeFormProductOnPostSubmit(...));

        $builder->addEventSubscriber($this->orderLineItemDraftDrySubmitListener);
        $builder->addEventSubscriber($this->orderLineItemDraftChecksumListener);
    }

    private function addFreeFormProductOnIsFreeFormPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $isFreeForm = $event->getData();

        if ($isFreeForm) {
            $form
                ->getParent()
                ->add('productSku', TextType::class, ['required' => true])
                ->add('freeFormProduct', TextType::class, ['required' => true]);
        }
    }

    private function addFreeFormProductOnIsFreeFormPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $isFreeForm = $form->getData();

        if ($isFreeForm) {
            $form
                ->getParent()
                ->add('productSku', TextType::class, ['required' => true])
                ->add('freeFormProduct', TextType::class, ['required' => true]);
        } else {
            $form
                ->getParent()
                ->remove('productSku')
                ->remove('freeFormProduct');
        }
    }

    private function fillFreeFormProductOnPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $event->getData();

        $product = $form->get('product')->getData();
        $isFreeForm = $form->get('isFreeForm')->getData();
        if ($product !== null && $isFreeForm) {
            $orderLineItem->setIsFreeForm(true);
            $orderLineItem->setProductSku($product->getSku());
            $orderLineItem->setFreeFormProduct($product->getDenormalizedDefaultName());
        }
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var OrderLineItem|null $orderLineItem */
        $orderLineItem = $form->getData();
        $tierPrices = [];
        if ($orderLineItem instanceof OrderLineItem) {
            $tierPrices = $this->tierPricesProvider->getTierPricesForLineItem($orderLineItem);
        }

        $view->vars['tierPrices'] = $tierPrices;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', OrderLineItem::class);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_order_line_item_draft';
    }
}
