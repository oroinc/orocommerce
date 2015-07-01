<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;

class QuoteProductType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product';

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
            ->add('product', ProductSelectType::NAME, [
                'required'          => true,
                'label'             => 'orob2b.product.entity_label',
                'create_enabled'    => false,
            ])
            ->add('productReplacement', ProductSelectType::NAME, [
                'required'          => false,
                'label'             => 'orob2b.sale.quoteproduct.productreplacement.label',
                'create_enabled'    => false,
            ])
            ->add(
                'quoteProductRequests',
                QuoteProductRequestCollectionType::NAME
            )
            ->add(
                'quoteProductOffers',
                QuoteProductOfferCollectionType::NAME,
                [
                    'add_label' => 'orob2b.sale.quoteproductoffer.add_label',
                ]
            )
            ->add(
                'type',
                'choice',
                [
                    'label' => 'orob2b.sale.quoteproduct.type.label',
                    'choices' => QuoteProduct::getTypeTitles(),
                    'required' => true,
                    'expanded' => false
                ]
            )
            ->add('commentCustomer', 'textarea', [
                'required'  => false,
                'disabled'  => true,
                'label'     => 'orob2b.sale.quoteproduct.commentcustomer.label'
            ])
            ->add('comment', 'textarea', [
                'required'  => false,
                'label'     => 'orob2b.sale.quoteproduct.comment.label'
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
            'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProduct',
            'intention'     => 'sale_quote_product',
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
        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $event->getData();
        $form = $event->getForm();

        if ($quoteProduct && null !== $quoteProduct->getId()) {
            $product = $quoteProduct->getProduct();
            if (!$product) {
                $form->add(
                    'product',
                    null,
                    [
                        'required'      => true,
                        'label'         => 'orob2b.product.entity_label',
                        'empty_value'   => $this->translator->trans(
                            'orob2b.sale.quoteproduct.product.removed',
                            [
                                '{title}' => $quoteProduct->getProductSku(),
                            ]
                        ),
                    ]
                );
            }
        }
    }
}
