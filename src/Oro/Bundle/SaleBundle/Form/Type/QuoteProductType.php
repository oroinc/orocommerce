<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds QuoteProductType form
 */
class QuoteProductType extends AbstractType
{
    const NAME = 'oro_sale_quote_product';

    /**
     * @var UnitLabelFormatterInterface
     */
    protected $labelFormatter;

    /**
     * @var QuoteProductFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $productUnitClass;

    public function __construct(
        TranslatorInterface $translator,
        UnitLabelFormatterInterface $labelFormatter,
        QuoteProductFormatter $formatter,
        ManagerRegistry $registry
    ) {
        $this->translator = $translator;
        $this->labelFormatter = $labelFormatter;
        $this->formatter = $formatter;
        $this->registry = $registry;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $productUnitClass
     */
    public function setProductUnitClass($productUnitClass)
    {
        $this->productUnitClass = $productUnitClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component'] = $options['page_component'];
        $view->vars['page_component_options'] = $options['page_component_options'];
        $view->vars['allow_add_free_form_items'] = $options['allow_add_free_form_items'];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $units = [];

        /* @var $products Product[] */
        $products = [];

        $isFreeForm = false;

        if ($view->vars['value']) {
            /* @var $quoteProduct QuoteProduct */
            $quoteProduct = $view->vars['value'];

            if ($quoteProduct->getProduct()) {
                $product = $quoteProduct->getProduct();
                $products[$product->getId()] = $product;
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

        $view->vars['componentOptions'] = [
            'units' => $units,
            'allUnits' => $this->getAllUnits($options['compact_units']),
            'typeOffer' => QuoteProduct::TYPE_OFFER,
            'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
            'compactUnits' => $options['compact_units'],
            'isFreeForm' => $isFreeForm,
            'allowEditFreeForm' => $options['allow_add_free_form_items'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductSelectType::class, [
                'required' => false,
                'label' => 'oro.product.entity_label',
                'create_enabled' => false,
                'data_parameters' => [
                    'scope' => 'quote'
                ]
            ])
            ->add('productSku', TextType::class, [
                'required' => false,
                'label' => 'oro.product.sku.label',
            ])
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
                ],
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
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'csrf_token_id' => 'sale_quote_product',
            'compact_units' => false,
            'allow_prices_override' => true,
            'allow_add_free_form_items' => true,
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'orosale/js/app/views/line-item-view'],
        ]);
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
     * @param bool $isCompactUnits
     * @return array
     */
    protected function getAllUnits($isCompactUnits)
    {
        $units = $this->registry->getManagerForClass($this->productUnitClass)
            ->getRepository($this->productUnitClass)
            ->getAllUnits()
        ;

        return $this->labelFormatter->formatChoices($units, $isCompactUnits);
    }
}
