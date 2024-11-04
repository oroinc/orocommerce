<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory\ProductLineItemsHolderFactoryInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a shopping list line item of a product kit.
 */
class ProductKitLineItemType extends AbstractType
{
    private FrontendProductPricesDataProvider $frontendProductPricesDataProvider;

    private ProductLineItemsHolderFactoryInterface $lineItemsHolderFactory;

    private SubtotalProviderInterface $lineItemNotPricedSubtotalProvider;

    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        ProductLineItemsHolderFactoryInterface $lineItemsHolderFactory,
        SubtotalProviderInterface $lineItemNotPricedSubtotalProvider
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->lineItemsHolderFactory = $lineItemsHolderFactory;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', QuantityType::class, ['constraints' => []])
            ->add('unit', ProductUnitSelectionType::class)
            ->add(
                $builder
                    ->create(
                        'kitItemLineItems',
                        CollectionType::class,
                        [
                            'required' => false,
                            'allow_add' => false,
                            'allow_delete' => false,
                            'entry_type' => ProductKitItemLineItemType::class,
                        ]
                    )
                    ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'])
            )
        ->add('notes', TextareaType::class, ['required' => false]);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        usort($view['kitItemLineItems']->children, function (FormView $formViewA, FormView $formViewB) {
            /** @var ProductKitItemLineItem $kiteItemLineItemA */
            $kiteItemLineItemA = $formViewA->vars['data'];
            /** @var ProductKitItemLineItem $kiteItemLineItemB */
            $kiteItemLineItemB = $formViewB->vars['data'];

            return $kiteItemLineItemA->getKitItem()->getSortOrder() <=>
                $kiteItemLineItemB->getKitItem()->getSortOrder();
        });

        $view['kitItemLineItems']->vars['productPrices'] = [];
        $view->vars['subtotal'] = null;

        /** @var LineItem|null $productKitLineItem */
        $productKitLineItem = $form->getData();
        if ($productKitLineItem !== null) {
            $view['kitItemLineItems']->vars['productPrices'] = $this->frontendProductPricesDataProvider
                ->getAllPricesForProducts($this->getAllProducts($productKitLineItem));

            $view->vars['subtotal'] = $this->lineItemNotPricedSubtotalProvider
                ->getSubtotal($this->lineItemsHolderFactory->createFromLineItems([$productKitLineItem]));
        }
    }

    /**
     * Collects all products related to line item.
     * Assumes that all collections are already initialized by this point, so iterating over them should not have
     * any performance impact.
     *
     * @param LineItem $lineItem
     * @return array<Product>
     */
    private function getAllProducts(LineItem $lineItem): array
    {
        return array_merge(
            [$lineItem->getProduct()],
            ...array_map(
                static function (ProductKitItemLineItem $kitItemLineItem) {
                    return (array)$kitItemLineItem->getKitItem()?->getProducts()->toArray();
                },
                $lineItem->getKitItemLineItems()->toArray()
            )
        );
    }

    public function onPostSubmit(PostSubmitEvent $event): void
    {
        /** @var Collection<ProductKitItemLineItem>|null $collection */
        $collection = $event->getData();
        if (!$collection instanceof Collection) {
            return;
        }

        foreach ($collection as $key => $kitItemLineItem) {
            if (!$kitItemLineItem->getProduct() && $kitItemLineItem->getKitItem()?->isOptional()) {
                $collection->remove($key);
            }
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LineItem::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_product_kit_line_item';
    }
}
