<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use OroB2B\Bundle\SaleBundle\Model\QuoteProductOfferMatcher;
use OroB2B\Bundle\SaleBundle\Form\DataTransformer\QuoteProductToOrderTransformer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\ConfigurableQuoteProductOffer;

class QuoteProductToOrderType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_to_order';

    const FIELD_QUANTITY = 'quantity';
    const FIELD_UNIT = 'unit';
    const FIELD_OFFER = 'offer'; // virtual field used in result data

    /**
     * @var QuoteProductOfferMatcher
     */
    protected $matcher;

    /**
     * @var RoundingService
     */
    protected $roundingService;

    /**
     * @param QuoteProductOfferMatcher $matcher
     * @param RoundingService $roundingService
     */
    public function __construct(QuoteProductOfferMatcher $matcher, RoundingService $roundingService)
    {
        $this->matcher = $matcher;
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $quoteProduct = $options['data'];
        if (!$quoteProduct instanceof QuoteProduct) {
            throw new UnexpectedTypeException($quoteProduct, 'QuoteProduct');
        }

        $builder
            ->add(
                self::FIELD_QUANTITY,
                'number',
                [
                    'constraints' => [new NotBlank(), new Decimal(), new GreaterThanZero()],
                    'read_only' => !$quoteProduct->hasIncrementalOffers(),
                ]
            )->add(
                self::FIELD_UNIT,
                'hidden'
            );

        $builder->addModelTransformer(
            new QuoteProductToOrderTransformer($this->matcher, $this->roundingService, $quoteProduct)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data']);
        $resolver->setDefaults(
            [
                'data_class' => null,
                'constraints' => new ConfigurableQuoteProductOffer(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['quoteProduct'] = $options['data'];

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
