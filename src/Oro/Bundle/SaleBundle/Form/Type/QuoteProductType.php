<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for QuoteProduct entity.
 */
class QuoteProductType extends AbstractType
{
    public function __construct(
        private UnitLabelFormatterInterface $labelFormatter,
        private ManagerRegistry $doctrine,
        private EventSubscriberInterface $quoteProductProductListener,
        private EventSubscriberInterface $quoteProductOfferChecksumListener
    ) {
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['page_component'] = $options['page_component'];
        $view->vars['page_component_options'] = $options['page_component_options'];
        $view->vars['allow_add_free_form_items'] = $options['allow_add_free_form_items'];
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $units = [];
        /* @var Product[] $products */
        $products = [];
        $productType = null;

        $isFreeForm = false;
        if ($view->vars['value']) {
            /* @var QuoteProduct $quoteProduct */
            $quoteProduct = $view->vars['value'];

            if ($quoteProduct->getProduct()) {
                $productType = $quoteProduct->getProduct()->getType();
                $product = $quoteProduct->getProduct();
                $products[$product->getId()] = $product;
            }

            if ($quoteProduct->getProduct()?->isKit()) {
                $quoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();
                $currency = $quoteProductOffer ? $quoteProductOffer->getPrice()?->getCurrency() : null;
            }

            if ($quoteProduct->getProductReplacement()) {
                $product = $quoteProduct->getProductReplacement();
                $products[$product->getId()] = $product;
            }

            $isFreeForm = $quoteProduct->isProductFreeForm() || $quoteProduct->isProductReplacementFreeForm();
        }

        foreach ($products as $product) {
            $units[$product->getId()] = $product->getSellUnitsPrecision();
        }

        // Set read-only attribute in this place for cases with backend validation
        if ($productType === Product::TYPE_KIT) {
            $this->setReadonlyForOfferPriceField($view);
        }

        $view->vars['componentOptions'] = [
            'units' => $units,
            'allUnits' => $this->getAllUnits($options['compact_units']),
            'typeOffer' => QuoteProduct::TYPE_OFFER,
            'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
            'compactUnits' => $options['compact_units'],
            'isFreeForm' => $isFreeForm,
            'allowEditFreeForm' => $options['allow_add_free_form_items'],
            'fullName' => $view->vars['full_name'],
            'productType' => $productType,
            'currency' => $currency ?? null,
        ];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                $builder
                    ->create(
                        'product',
                        ProductSelectType::class,
                        [
                            'required'  => true,
                            'autocomplete_alias' => 'oro_sale_product_visibility_limited',
                            'grid_name' => 'products-select-grid',
                            'grid_parameters' => [
                                'types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]
                            ],
                            'label' => 'oro.product.entity_label',
                            'create_enabled' => false,
                            'data_parameters' => [
                                'scope' => 'quote',
                            ],
                        ]
                    )
                    ->addEventSubscriber($this->quoteProductProductListener)
            )
            ->add('productSku', TextType::class, [
                'required' => false,
                'label' => 'oro.product.sku.label',
            ])
            ->add(
                'kitItemLineItems',
                QuoteProductKitItemLineItemCollectionType::class,
                ['required' => false, 'currency' => $options['currency']]
            )
            ->add('productReplacement', ProductSelectType::class, [
                'required' => false,
                'label' => 'oro.sale.quoteproduct.product_replacement.label',
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'quote'
                ]
            ])
            ->add('productReplacementSku', TextType::class, [
                'required' => false,
                'label' => 'oro.product.sku.label',
            ])
            ->add('freeFormProduct', TextType::class, [
                'required' => false,
                'label' => 'oro.product.entity_label',
            ])
            ->add('freeFormProductReplacement', TextType::class, [
                'required' => false,
                'label' => 'oro.sale.quoteproduct.product_replacement.label',
            ])
            ->add('quoteProductOffers', QuoteProductOfferCollectionType::class, [
                'add_label' => 'oro.sale.quoteproductoffer.add_label',
                'entry_options' => [
                    'compact_units' => $options['compact_units'],
                    'allow_prices_override' => $options['allow_prices_override'],
                ]
            ])
            ->add('type', HiddenType::class, [
                'data' => QuoteProduct::TYPE_REQUESTED,
            ])
            ->add('commentCustomer', TextareaType::class, [
                'required' => false,
                'label' => 'oro.sale.quoteproduct.comment_customer.label',
                'attr' => [
                    'readonly' => true
                ]
            ])
            ->add('comment', TextareaType::class, [
                'required' => false,
                'label' => 'oro.sale.quoteproduct.comment.label',
            ]);

        $builder->addEventSubscriber($this->quoteProductOfferChecksumListener);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteProduct::class,
            'csrf_token_id' => 'sale_quote_product',
            'compact_units' => false,
            'allow_prices_override' => true,
            'allow_add_free_form_items' => true,
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'orosale/js/app/views/line-item-view'],
        ]);

        $resolver
            ->define('currency')
            ->default(null)
            ->allowedTypes('string', 'null');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_sale_quote_product';
    }

    private function getAllUnits(bool $isCompactUnits): array
    {
        $units = $this->doctrine->getRepository(ProductUnit::class)->getAllUnits();

        return $this->labelFormatter->formatChoices($units, $isCompactUnits);
    }

    private function setReadonlyForOfferPriceField(FormView $view): void
    {
        foreach ($view->children['quoteProductOffers'] as $child) {
            if (!$child->children['price']) {
                continue;
            }

            $child->children['price']->children['value']->vars['attr']['readonly'] = true;
        }
    }
}
