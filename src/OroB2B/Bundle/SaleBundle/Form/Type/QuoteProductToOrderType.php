<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\SaleBundle\Form\DataTransformer\QuoteProductToOrderTransformer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\ConfigurableQuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductToOrderType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_to_order';

    const FIELD_OFFER = 'offer';
    const FIELD_QUANTITY = 'quantity';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ProductUnitValueFormatter
     */
    protected $unitFormatter;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @param TranslatorInterface $translator
     * @param ProductUnitValueFormatter $unitFormatter
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(
        TranslatorInterface $translator,
        ProductUnitValueFormatter $unitFormatter,
        NumberFormatter $numberFormatter
    ) {
        $this->translator = $translator;
        $this->unitFormatter = $unitFormatter;
        $this->numberFormatter = $numberFormatter;
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

        /** @var QuoteProductOffer $firstQuoteProductOffer */
        $firstQuoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();

        $builder
            ->add(
                self::FIELD_OFFER,
                'choice',
                [
                    'choices' => $this->getOfferChoices($quoteProduct),
                    'expanded' => true,
                    'constraints' => [new NotBlank()]
                ]
            )
            ->add(
                self::FIELD_QUANTITY,
                'number',
                [
                    'constraints' => [new NotBlank(), new Decimal(), new GreaterThanZero()],
                    'attr' => [
                        'disabled' => $firstQuoteProductOffer ? !$firstQuoteProductOffer->isAllowIncrements() : true
                    ]
                ]
            );

        $builder->addModelTransformer(new QuoteProductToOrderTransformer($quoteProduct));
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
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $options['data'];

        $view->vars['quote_product'] = $quoteProduct;

        $offers = [];
        foreach ($quoteProduct->getQuoteProductOffers() as $offer) {
            $offers[$offer->getId()] = $offer;
        }

        /** @var FormView $offerView */
        $offerView = $view->children[self::FIELD_OFFER];
        /** @var FormView $optionView */
        foreach ($offerView->children as $optionView) {
            $optionValue = $optionView->vars['value'];
            if (isset($offers[$optionValue])) {
                /** @var QuoteProductOffer $quoteProductOffer */
                $quoteProductOffer = $offers[$optionValue];
                $optionView->vars['offer'] = $quoteProductOffer;
                $optionView->vars['attr'] = array_merge(
                    $optionView->vars['attr'],
                    [
                        'data-unit' => $quoteProductOffer->getProductUnitCode(),
                        'data-quantity' => $quoteProductOffer->getQuantity(),
                        'data-allow-increment' => (string)$quoteProductOffer->isAllowIncrements(),
                        'data-price' => $this->numberFormatter->formatCurrency(
                            $quoteProductOffer->getPrice()->getValue(),
                            $quoteProductOffer->getPrice()->getCurrency()
                        ),
                    ]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param QuoteProduct $quoteProduct
     * @return array
     */
    protected function getOfferChoices(QuoteProduct $quoteProduct)
    {
        $choices = [];

        foreach ($quoteProduct->getQuoteProductOffers() as $offer) {
            // only unit offers are allowed
            if ($offer->getPriceType() == QuoteProductOffer::PRICE_TYPE_UNIT) {
                $label = $this->unitFormatter->formatShort(
                    $offer->getQuantity(),
                    $offer->getProductUnit()
                );
                if ($offer->isAllowIncrements()) {
                    $label .= ' ' . $this->translator->trans(
                        'orob2b.frontend.sale.quoteproductoffer.allow_increments.label'
                    );
                }

                $offerId = $offer->getId();
                if ($offerId) {
                    $choices[$offerId] = $label;
                }
            }
        }

        return $choices;
    }
}
