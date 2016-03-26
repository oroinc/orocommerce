<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductSelectedOffer;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\EventListener\QuoteToOrderResizeFormSubscriber;

/**
 * Class extended from collection to override default form listener
 */
class QuoteToOrderType extends CollectionType
{
    const NAME = 'orob2b_sale_quote_to_order';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // custom subscriber to pass data to child form types
        $resizeSubscriber = new QuoteToOrderResizeFormSubscriber(
            $options['type'],
            $options['options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        );
        $builder->addEventSubscriber($resizeSubscriber);

        // must be run before ResizeFormListener
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData'], 10);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit'], 10);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $this->quote = $event->getData();
        if (!$this->quote instanceof Quote) {
            throw new UnexpectedTypeException($this->quote, 'Quote');
        }
        $event->setData($this->quote->getQuoteProducts()->toArray());
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $em = $this->registry->getManagerForClass('OroB2BSaleBundle:QuoteProductSelectedOffer');
        $selectedOffers = $em->getRepository('OroB2BSaleBundle:QuoteProductSelectedOffer')
            ->getSavedOffersByQuote($this->quote);
        foreach ($event->getData() as $item) {
            /** @var QuoteProductOffer $offer */
            $offer = $item['offer'];
            $selectedOffer = null;
            if (array_key_exists($offer->getId(), $selectedOffers)) {
                $selectedOffer = $selectedOffers[$offer->getId()];
            }
            unset($selectedOffers[$offer->getId()]);
            if (!$selectedOffer) {
                $selectedOffer = new QuoteProductSelectedOffer($this->quote, $offer, $item['quantity']);
                $em->persist($selectedOffer);
            } else {
                $selectedOffer->setQuote($this->quote);
                $selectedOffer->setQuoteProductOffer($offer);
                $selectedOffer->setQuantity($item['quantity']);
            }
        }
        foreach ($selectedOffers as $unusedItem) {
            $em->remove($unusedItem);
        }
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $em = $this->registry->getManagerForClass('OroB2BSaleBundle:QuoteProductSelectedOffer');
        $selectedItems = $em->getRepository('OroB2BSaleBundle:QuoteProductSelectedOffer')
            ->findBy(['quote' => $this->quote]);
        if ($selectedItems) {
            foreach ($view->children as $view) {
                /** @var QuoteProduct $quoteProduct */
                if (array_key_exists('quoteProduct', $view->vars)) {
                    $quoteProduct = $view->vars['quoteProduct'];

                    $selectedId = $this->getSelectedId($quoteProduct, $selectedItems);
                    if ($selectedId) {
                        $view->vars['selectedOfferId'] = $selectedId;
                    }
                }
            }
        }
    }

    /**
     * @param QuoteProduct $quoteProduct
     * @param QuoteProductSelectedOffer[] $selectedItems
     * @return integer|null
     */
    protected function getSelectedId($quoteProduct, $selectedItems)
    {
        foreach ($selectedItems as $item) {
            $productOffer = $item->getQuoteProductOffer();
            if ($productOffer->getQuoteProduct()->getId() == $quoteProduct->getId()) {
                return $productOffer->getId();
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['data']);
        $resolver->setDefaults(['data_class' => null, 'type' => QuoteProductToOrderType::NAME]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
