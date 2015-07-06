<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductOfferType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_offer';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', 'integer', [
                'required'  => true,
                'label'     => 'orob2b.sale.quoteproductoffer.quantity.label'
            ])
            ->add('price', PriceType::NAME, [
                'required'  => true,
                'label'     => 'orob2b.sale.quoteproductoffer.pricetype.label'
            ])
            ->add('priceType', 'choice', [
                    'label' => 'orob2b.sale.quoteproductoffer.pricetype.label',
                    'choices' => QuoteProductOffer::getPriceTypeTitles(),
                    'required' => true,
                    'expanded' => true
            ])
            ->add('allowIncrements', 'checkbox', [
                'required'  => false,
                'label'     => 'orob2b.sale.quoteproductoffer.allowincrements.label'
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
            'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
            'intention'     => 'sale_quote_product_offer',
            'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"'
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
        /* @var $quoteProductOffer QuoteProductOffer */
        $quoteProductOffer = $event->getData();
        $form = $event->getForm();
        $choices = [];

        if ($quoteProductOffer && null !== $quoteProductOffer->getId()) {
            $product = $quoteProductOffer->getQuoteProduct()->getProduct();
            if ($product) {
                foreach ($product->getUnitPrecisions() as $unitPrecision) {
                    $choices[] = $unitPrecision->getUnit();
                }
            }
        }

        $this->processProductUnitField($form, $quoteProductOffer, $choices);
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
                'label'     => 'orob2b.product.productunit.entity_label',
            ]
        );
    }

    /**
     * @param FormInterface $form
     * @param QuoteProductOffer|null $quoteProductOffer
     * @param array|null $choices
     */
    protected function processProductUnitField(FormInterface $form, $quoteProductOffer, $choices)
    {
        $productUnitOptions = [
            'required'  => true,
            'label'     => 'orob2b.product.productunit.entity_label',
            'choices'  => $choices,
        ];
        if ($quoteProductOffer && null !== $quoteProductOffer->getId()) {
            $product = $quoteProductOffer->getQuoteProduct()->getProduct();

            $productUnit = $quoteProductOffer->getProductUnit();
            if ($quoteProductOffer->getProductUnitCode()
                && ($product && !in_array($productUnit->getCode(), $choices, true))
            ) {
                // productUnit was removed
                $productUnitOptions['empty_value']  = $this->translator->trans(
                    'orob2b.sale.quoteproduct.product.removed',
                    [
                        '{title}' => $quoteProductOffer->getProductUnitCode(),
                    ]
                );
            } else {
                // empty productUnit
                $productUnitOptions['empty_value']  = $this->translator->trans(
                    'orob2b.sale.quoteproductoffer.product_unit.empty'
                );
            }
        }

        $form->add(
            'productUnit',
            ProductUnitSelectionType::NAME,
            $productUnitOptions
        );
    }
}
