<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;

class QuoteProductType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product';

    /**
     * @var ProductUnitLabelFormatter
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
     * @var string
     */
    protected $dataClass;

    /**
     * @param TranslatorInterface $translator
     * @param ProductUnitLabelFormatter $labelFormatter
     * @param QuoteProductFormatter $formatter
     */
    public function __construct(
        TranslatorInterface $translator,
        ProductUnitLabelFormatter $labelFormatter,
        QuoteProductFormatter $formatter
    ) {
        $this->translator = $translator;
        $this->labelFormatter = $labelFormatter;
        $this->formatter = $formatter;
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
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component'] = $options['page_component'];
        $view->vars['page_component_options'] = $options['page_component_options'];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $units = [];

        /* @var $products Product[] */
        $products = [];

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
        }

        foreach ($products as $product) {
            $units[$product->getId()] = [];

            foreach ($product->getAvailableUnitCodes() as $unitCode) {
                $units[$product->getId()][$unitCode] = $this->labelFormatter->format(
                    $unitCode,
                    $options['compact_units']
                );
            }
        }

        $componentOptions = [
            'units' => $units,
            'typeOffer' => QuoteProduct::TYPE_OFFER,
            'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
            'compactUnits' => $options['compact_units'],
        ];

        $view->vars['componentOptions'] = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductRemovedSelectType::NAME, [
                'required' => true,
                'label' => 'orob2b.product.entity_label',
                'create_enabled' => false,
            ])
            ->add('productReplacement', ProductSelectType::NAME, [
                'required' => false,
                'label' => 'orob2b.sale.quoteproduct.product_replacement.label',
                'create_enabled' => false,
            ])
            ->add('quoteProductOffers', QuoteProductOfferCollectionType::NAME, [
                'add_label' => 'orob2b.sale.quoteproductoffer.add_label',
                'options' => [
                    'compact_units' => $options['compact_units'],
                ],
            ])
            ->add('type', 'hidden', [
                'data' => QuoteProduct::TYPE_REQUESTED,
                //'label' => 'orob2b.sale.quoteproduct.type.label',
                //'choices' => $this->formatter->formatTypeLabels(QuoteProduct::getTypes()),
                //'required' => true,
                //'expanded' => false,
            ])
            ->add('commentAccount', 'textarea', [
                'required' => false,
                'read_only' => true,
                'label' => 'orob2b.sale.quoteproduct.comment_account.label',
            ])
            ->add('comment', 'textarea', [
                'required' => false,
                'label' => 'orob2b.sale.quoteproduct.comment.label',
            ])

        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'sale_quote_product',
            'compact_units' => false,
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => ['view' => 'orob2bsale/js/app/views/line-item-view'],
        ]);
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
    public function preSetData(FormEvent $event)
    {
        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $event->getData();

        if (!$quoteProduct || null === $quoteProduct->getId()) {
            return;
        }

        $form = $event->getForm();

        if ($quoteProduct->isTypeNotAvailable() && !$quoteProduct->getProductReplacement()) {
            $options = [
                'create_enabled' => false,
                'required' => false,
                'label' => 'orob2b.sale.quoteproduct.product_replacement.label',
            ];

            $emptyValueTitle = $this->translator->trans('orob2b.product.removed', [
                '{title}' => $quoteProduct->getProductReplacementSku(),
            ]);

            $options['configs'] = [
                'placeholder' => $emptyValueTitle,
            ];

            $form->add('productReplacement', ProductSelectType::NAME, $options);
        }
    }
}
