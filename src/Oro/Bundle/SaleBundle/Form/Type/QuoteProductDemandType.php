<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;

class QuoteProductDemandType extends AbstractType
{
    const NAME = 'oro_sale_quote_product_demand';

    const FIELD_QUANTITY = 'quantity';
    const FIELD_QUOTE_PRODUCT_OFFER = 'quoteProductOffer';
    const FIELD_UNIT = 'unit';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\SaleBundle\Entity\QuoteProductDemand'
            ]
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var QuoteProductDemand $quoteProductDemand */
        $quoteProductDemand = $options['data'];

        $quoteProduct = $quoteProductDemand->getQuoteProductOffer()->getQuoteProduct();
        $builder
            ->add(
                self::FIELD_QUANTITY,
                'number',
                [
                    'constraints' => [new NotBlank(), new Decimal(), new GreaterThanZero()],
                    'read_only' => !$quoteProduct->hasIncrementalOffers(),
                    'required' => true
                ]
            )->add(
                self::FIELD_QUOTE_PRODUCT_OFFER,
                QuoteProductDemandOfferChoiceType::NAME,
                [
                    'choices' => $quoteProduct->getQuoteProductOffers(),
                    'required' => true
                ]
            )->add(
                self::FIELD_UNIT,
                'hidden',
                [
                    'mapped' => false,
                    'data' => $quoteProductDemand->getQuoteProductOffer()->getProductUnitCode()
                ]
            );

        // Make sure that form is workable even if offer field was removed
        $builder->get(self::FIELD_QUOTE_PRODUCT_OFFER)->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($quoteProductDemand) {
                $data = $event->getData();
                if (!$data) {
                    $event->setData($quoteProductDemand->getQuoteProductOffer());
                }
            }
        );
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($quoteProductDemand) {
                $data = $event->getData();
                if (!array_key_exists(self::FIELD_QUOTE_PRODUCT_OFFER, $data)) {
                    $data[self::FIELD_QUANTITY] = $quoteProductDemand->getQuoteProductOffer()->getQuantity();
                    $data[self::FIELD_UNIT] = $quoteProductDemand->getQuoteProductOffer()->getProductUnitCode();
                    $event->setData($data);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var QuoteProductDemand $quoteProductDemand */
        $quoteProductDemand = $options['data'];
        $view->vars['quoteProduct'] = $quoteProductDemand->getQuoteProductOffer()->getQuoteProduct();

        /** @var FormView $quantityView */
        $quantityView = $view->children[self::FIELD_QUANTITY];
        $quantityView->vars['attr']['data-validation'] = json_encode(
            array_merge(
                ['AllowedQuoteDemandQuantity' => []],
                json_decode($quantityView->vars['attr']['data-validation'], true)
            )
        );
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
}
