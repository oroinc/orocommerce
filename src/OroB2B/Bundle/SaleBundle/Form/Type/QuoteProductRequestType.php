<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\OptionalPriceType as PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

class QuoteProductRequestType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_request';

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
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', 'integer', [
                'required'  => false,
                'label'     => 'orob2b.sale.quoteproductrequest.quantity.label',
            ])
            ->add('price', PriceType::NAME, [
                'required'  => false,
                'label'     => 'orob2b.sale.quoteproductrequest.price.label',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'    => $this->dataClass,
            'intention'     => 'sale_quote_product_request',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
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
        /* @var $quoteProductRequest QuoteProductRequest */
        $quoteProductRequest = $event->getData();
        $form = $event->getForm();
        $choices = [];

        $productUnitOptions = [
            'required'  => true,
            'label'     => 'orob2b.product.productunit.entity_label',
        ];

        if ($quoteProductRequest && null !== $quoteProductRequest->getId()) {
            $product = $quoteProductRequest->getQuoteProduct()->getProduct();
            if ($product) {
                foreach ($product->getUnitPrecisions() as $unitPrecision) {
                    $choices[] = $unitPrecision->getUnit();
                }
            }
            $productUnit = $quoteProductRequest->getProductUnit();
            if (!$productUnit || ($product && !in_array($productUnit, $choices, true))) {
                // ProductUnit was removed
                $productUnitOptions['empty_value']  = $this->translator->trans(
                    'orob2b.sale.quoteproduct.product.removed',
                    [
                        '{title}' => $quoteProductRequest->getProductUnitCode(),
                    ]
                );
            }
        }

        $productUnitOptions['choices'] = $choices;

        $form->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            $productUnitOptions
        );
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $event->getForm()->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            [
                'label' => 'orob2b.product.productunit.entity_label',
            ]
        );
    }
}
