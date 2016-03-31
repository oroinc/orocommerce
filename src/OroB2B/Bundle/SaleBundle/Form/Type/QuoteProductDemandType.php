<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\ConfigurableQuoteProductOffer;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;

class QuoteProductDemandType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_demand';
    const FIELD_QUANTITY = 'quantity';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefaults(
            [
                'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductDemand',
                'constraints' => new ConfigurableQuoteProductOffer(), // TODO Refactor constraint
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
                ]
            )->add(
                'quoteProductOffer',
                QuoteProductDemandOfferChoiceType::NAME,
                [
                    'choices' => $quoteProduct->getQuoteProductOffers()
                ]
            )->add(
                'unit',
                'hidden',
                [
                    'mapped' => false,
                    'data' => $quoteProductDemand->getQuoteProductOffer()->getProductUnitCode()
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var QuoteProductDemand $quoteProductDemand */
        $quoteProductDemand = $options['data'];
        // TODO: Review usage on template and refactor if required
        $view->vars['quoteProduct'] = $quoteProductDemand->getQuoteProductOffer()->getQuoteProduct();

        // move constraint to quantity field to support JS validation
        /** @var FormView $quantityView */
        $quantityView = $view->children[self::FIELD_QUANTITY];
        if (isset($view->vars['attr']['data-validation'], $quantityView->vars['attr']['data-validation'])) {
            $viewAttr = $view->vars['attr']['data-validation'];
            $quantityViewAttr = $quantityView->vars['attr']['data-validation'];

            $quantityView->vars['attr']['data-validation'] = json_encode(
                array_merge(json_decode($viewAttr, true), json_decode($quantityViewAttr, true))
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
