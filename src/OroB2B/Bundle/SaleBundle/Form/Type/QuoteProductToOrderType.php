<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitValueFormatter;
use OroB2B\Bundle\SaleBundle\Form\DataTransformer\QuoteProductToOrderTransformer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\ConfigurableQuoteProductOffer;

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
     * @param TranslatorInterface $translator
     * @param ProductUnitValueFormatter $unitFormatter
     */
    public function __construct(TranslatorInterface $translator, ProductUnitValueFormatter $unitFormatter)
    {
        $this->translator = $translator;
        $this->unitFormatter = $unitFormatter;
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
                self::FIELD_OFFER,
                'choice',
                ['choices' => $this->getOfferChoices($quoteProduct), 'expanded' => true]
            )
            ->add(
                self::FIELD_QUANTITY,
                'number',
                ['constraints' => [new Decimal(), new GreaterThanZero()]]
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
                $optionView->vars['offer'] = $offers[$optionValue];
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
            $label = $this->unitFormatter->formatShort(
                $offer->getQuantity(),
                $offer->getProductUnit()
            );
            if ($offer->isAllowIncrements()) {
                $label .= ' '.$this->translator->trans(
                        'orob2b.frontend.sale.quoteproductoffer.allow_increments.label'
                    );
            }

            $offerId = $offer->getId();
            if ($offerId) {
                $choices[$offerId] = $label;
            }
        }

        return $choices;
    }
}
