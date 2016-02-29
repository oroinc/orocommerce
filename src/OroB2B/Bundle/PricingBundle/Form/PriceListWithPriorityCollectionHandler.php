<?php

namespace OroB2B\Bundle\PricingBundle\Form;

use Doctrine\ORM\EntityManagerInterface;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;

class PriceListWithPriorityCollectionHandler
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;


    public function handleChanges($submitted, $existing)
    {

    }

    /**
     * @param BasePriceListRelation[] $actualPriceListsToTargetEntity
     * @param array $submittedPriceLists
     * @param EntityManagerInterface $em
     * @return bool
     */
    protected function removePriceListRelations(
        $actualPriceListsToTargetEntity,
        $submittedPriceLists,
        EntityManagerInterface $em
    ) {
        $hasChanges = false;
        /** @var BasePriceListRelation[] $actualPriceListsToTargetEntity */
        foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
            if (!in_array($priceListToTargetEntity->getPriceList(), $submittedPriceLists, true)) {
                $em->remove($priceListToTargetEntity);
                $hasChanges = true;
            }
        }

        return $hasChanges;
    }

    /**
     * @param FormInterface $priceListsByWebsite
     * @return array
     */
    protected function getWebsiteSubmittedPriceLists($priceListsByWebsite)
    {
        $submittedPriceLists = [];

        foreach ($priceListsByWebsite->get(PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD)->getData() as $item) {
            $submittedPriceLists[] = $item[PriceListSelectWithPriorityType::PRICE_LIST_FIELD];
        }

        return $submittedPriceLists;
    }
}
