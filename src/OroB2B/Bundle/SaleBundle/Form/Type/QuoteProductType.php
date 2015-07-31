<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;

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
                $units[$product->getId()][$unitCode] = $this->labelFormatter->format($unitCode);
            }
        }

        $componentOptions = [
            'units' => $units,
            'typeOffer' => QuoteProduct::TYPE_OFFER,
            'typeReplacement' => QuoteProduct::TYPE_NOT_AVAILABLE,
        ];

        $view->vars['componentOptions'] = $componentOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('product', ProductSelectType::NAME, [
                'required' => true,
                'label' => 'orob2b.product.entity_label',
                'create_enabled' => false,
            ])
            ->add('productReplacement', ProductSelectType::NAME, [
                'required' => false,
                'label' => 'orob2b.sale.quoteproduct.product_replacement.label',
                'create_enabled' => false,
            ])
            ->add('quoteProductRequests', QuoteProductRequestCollectionType::NAME, [
            ])
            ->add('quoteProductOffers', QuoteProductOfferCollectionType::NAME, [
                'add_label' => 'orob2b.sale.quoteproductoffer.add_label',
            ])
            ->add('type', 'choice', [
                'label' => 'orob2b.sale.quoteproduct.type.label',
                'choices' => $this->formatter->formatTypeLabels(QuoteProduct::getTypes()),
                'required' => true,
                'expanded' => false,
            ])
            ->add('commentCustomer', 'textarea', [
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'sale_quote_product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
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

        if (!$quoteProduct->getProduct()) {
            $this->replaceProductField(
                $form,
                'product',
                true,
                $quoteProduct->getProductSku(),
                'orob2b.product.entity_label',
                'orob2b.sale.quoteproduct.product.removed'
            );
        }

        if ($quoteProduct->isTypeNotAvailable() && !$quoteProduct->getProductReplacement()) {
            $this->replaceProductField(
                $form,
                'productReplacement',
                false,
                $quoteProduct->getProductReplacementSku(),
                'orob2b.sale.quoteproduct.product_replacement.label',
                'orob2b.sale.quoteproduct.product_replacement.removed'
            );
        }
    }

    /**
     * @param FormInterface $form
     * @param string $field
     * @param bool $required
     * @param string $productSku
     * @param string $label
     * @param string $emptyLabel
     */
    protected function replaceProductField(
        FormInterface $form,
        $field,
        $required,
        $productSku,
        $label,
        $emptyLabel = null
    ) {
        $options = [
            'create_enabled' => false,
            'required' => $required,
            'label' => $label,
        ];

        if ($emptyLabel) {
            $emptyValueTitle = $this->translator->trans($emptyLabel, [
                '{title}' => $productSku,
            ]);

            $options['configs'] = [
                'placeholder' => $emptyValueTitle,
            ];
        }

        $form->add($field, ProductSelectType::NAME, $options);
    }
}
