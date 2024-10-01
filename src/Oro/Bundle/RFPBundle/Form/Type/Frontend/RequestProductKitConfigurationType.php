<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitChoiceType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Form type that represents an RFP request product kit configuration.
 */
class RequestProductKitConfigurationType extends AbstractType
{
    private FrontendProductPricesDataProvider $frontendProductPricesDataProvider;

    private DataTransformerInterface $productToIdDataTransformer;

    private EventSubscriberInterface $requestProductProductListener;

    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        DataTransformerInterface $productToIdDataTransformer,
        EventSubscriberInterface $requestProductProductListener
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->productToIdDataTransformer = $productToIdDataTransformer;
        $this->requestProductProductListener = $requestProductProductListener;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Holds the index of the RequestProduct currently being configured.
            ->add('index', HiddenType::class, ['mapped' => false, 'constraints' => [new Type(['type' => 'int'])]])
            ->add(
                $builder
                    ->create('product', HiddenType::class)
                    ->addModelTransformer($this->productToIdDataTransformer)
                    ->addEventSubscriber($this->requestProductProductListener)
                    ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'setProductUnitProduct'])
                    ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'setProductUnitProduct'])
            )
            ->add(
                'kitItemLineItems',
                RequestProductKitItemLineItemCollectionType::class,
                ['entry_options' => ['set_default_data' => true]]
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'mapped' => false,
                    'required' => true,
                    'default_data' => 1.0,
                    'useInputTypeNumberValueFormat' => true,
                ]
            )
            ->add(
                'productUnit',
                ProductUnitChoiceType::class,
                [
                    'mapped' => false,
                    'required' => true,
                    'compact' => false,
                    'sell' => true,
                ]
            );
    }

    public function setProductUnitProduct(FormEvent $event): void
    {
        /** @var Product|null $product */
        $product = $event->getForm()->getData();
        $formParent = $event->getForm()->getParent();
        // FormUtils::replaceField() is not used here on purpose as it breaks the default value callbacks that depend on
        // the option "product", such as for "choices" and "choice_filter" options.
        $formParent?->add(
            'productUnit',
            ProductUnitChoiceType::class,
            [
                'mapped' => false,
                'required' => true,
                'product' => $product,
                'compact' => false,
                'sell' => true,
            ]
        );
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var Product|null $product */
        $product = $form->get('product')->getData();

        $view['kitItemLineItems']->vars['product_prices'] = $product
            ? $this->frontendProductPricesDataProvider->getAllPricesForProducts($this->getAllProducts($product))
            : [];
    }

    /**
     * Collects all products related to line item.
     * Assumes that all collections are already initialized by this point, so iterating over them should not have
     * any performance impact.
     *
     * @param Product $product
     * @return array<Product>
     */
    private function getAllProducts(Product $product): array
    {
        return array_merge(
            [$product],
            ...array_map(
                static fn (ProductKitItem $kitItem) => (array)$kitItem->getProducts()->toArray(),
                $product->getKitItems()->toArray()
            )
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RequestProduct::class,
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_rfp_frontend_request_product_kit_configuration';
    }
}
