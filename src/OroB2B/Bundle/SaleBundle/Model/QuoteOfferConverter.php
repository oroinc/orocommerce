<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

use OroB2B\Bundle\SaleBundle\Entity\Repository\QuoteProductOfferRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class QuoteOfferConverter
{
    const OFFER_ID = 'offer_id';
    const OFFER = 'offer';
    const QUANTITY = 'quantity';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var QuoteProductOfferRepository
     */
    protected $quoteProductOfferRepository;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param array $offers
     * @return array
     */
    public function toArray(array $offers)
    {
        $result = [];
        foreach ($offers as $offer) {
            /** @var QuoteProductOffer $offerEntity */
            $offerEntity = $offer[self::OFFER];
            $res[self::OFFER_ID] = $offerEntity->getId();
            $res[self::QUANTITY] = $offer[self::QUANTITY];
            $result[] = $res;
        }

        return $result;
    }

    /**
     * @param array $offers
     * @return array
     */
    public function toModel(array $offers)
    {
        $result = [];
        $ids = array_map(
            function ($offer) {
                return $offer[self::OFFER_ID];
            },
            $offers
        );
        $offerEntities = $this->getQuoteProductOfferRepository()
            ->getOffersByIds($ids);
        
        foreach ($offerEntities as $offer) {
            $result[] = [self::QUANTITY => $offer->getQuantity(), self::OFFER => $offer];
        }

        return $result;
    }

    /**
     * @return QuoteProductOfferRepository
     */
    public function getQuoteProductOfferRepository()
    {
        if ($this->quoteProductOfferRepository === null) {
            $this->quoteProductOfferRepository = $this->registry
                ->getManagerForClass('OroB2BSaleBundle:QuoteProductOffer')
                ->getRepository('OroB2BSaleBundle:QuoteProductOffer');
        }

        return $this->quoteProductOfferRepository;
    }
}
