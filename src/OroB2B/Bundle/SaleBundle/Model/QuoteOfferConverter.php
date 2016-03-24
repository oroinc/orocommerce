<?php

namespace OroB2B\Bundle\SaleBundle\Model;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class QuoteOfferConverter
{
    const OFFER_ID = 'offer_id';
    const OFFER = 'offer';
    const QUANTITY = 'quantity';

    /** @var ManagerRegistry */
    protected $registry;

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
            $offerEntity = $offer['offer'];
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
        $offerEntities = $this->registry
            ->getManagerForClass('OroB2BSaleBundle:QuoteProductOffer')
            ->getRepository('OroB2BSaleBundle:QuoteProductOffer')
            ->getOffersByIds($this->getIds($offers));
        foreach ($offers as $offer) {
            $res[self::QUANTITY] = $offer[self::QUANTITY];
            $res[self::OFFER] = $this->getOfferById($offer[self::OFFER_ID], $offerEntities);
            $result[] = $res;
        }

        return $result;
    }

    /**
     * @param array $offers
     * @return integer[]
     */
    protected function getIds(array $offers)
    {
        $result = [];
        foreach ($offers as $offer) {
            $result[] = $offer[self::OFFER_ID];
        }

        return $result;
    }

    /**
     * @param integer $id
     * @param QuoteProductOffer[] $offers
     * @return QuoteProductOffer|null
     */
    protected function getOfferById($id, $offers)
    {
        foreach ($offers as $offer) {
            if ($offer->getId() == $id) {
                return $offer;
            }
        }

        return null;
    }
}
