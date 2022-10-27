<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Quote product quote demand type which represents all data related to quote demand
 */
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
        $attr = [];

        if (!$quoteProduct->hasIncrementalOffers()) {
            $attr['readonly'] = true;
        }

        $builder
            ->add(
                self::FIELD_QUANTITY,
                QuantityType::class,
                [
                    'constraints' => [new NotBlank(), new Decimal(), new GreaterThanZero()],
                    'required' => true,
                    'attr' => $attr,
                    'useInputTypeNumberValueFormat' => true
                ]
            )->add(
                self::FIELD_QUOTE_PRODUCT_OFFER,
                QuoteProductDemandOfferChoiceType::class,
                [
                    'choices' => $quoteProduct->getQuoteProductOffers(),
                    'required' => true
                ]
            )->add(
                self::FIELD_UNIT,
                HiddenType::class,
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

        $view->vars['unitPrecisions'] = [];
        if ($quoteProductDemand->getQuoteProductOffer()->getQuoteProduct()->getProduct()) {
            $view->vars['unitPrecisions'] = $this->getUnitPrecisions(
                $quoteProductDemand->getQuoteProductOffer()->getQuoteProduct()->getProduct()
            );
        }

        /** @var FormView $quantityView */
        $quantityView = $view->children[self::FIELD_QUANTITY];
        $quantityView->vars['attr']['data-validation'] = json_encode(
            array_merge(
                ['AllowedQuoteDemandQuantity' => [['message' => null ]] ],
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

    /**
     * Returns list of units with precisions
     * [ "<unitCode>" => <unitPrecision>, ... ]
     *
     * @param Product $product
     * @return array
     */
    protected function getUnitPrecisions(Product $product)
    {
        $data = [];
        foreach ($product->getUnitPrecisions() as $precision) {
            $data[$precision->getProductUnitCode()] = $precision->getPrecision();
        }

        return $data;
    }
}
