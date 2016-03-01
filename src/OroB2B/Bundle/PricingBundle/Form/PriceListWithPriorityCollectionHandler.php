<?php

namespace OroB2B\Bundle\PricingBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;

class PriceListWithPriorityCollectionHandler
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var string
     */
    protected $relationClass;

    /**
     * @param array $submitted
     * @param array|BasePriceListRelation[] $existing
     * @param object $targetEntity
     * @param Website $website
     * @return bool
     */
    public function handleChanges(array $submitted, array $existing, $targetEntity, Website $website)
    {
        $hasChanges = false;
        foreach ($submitted as $submittedItem) {
            $priceList = $submittedItem[PriceListSelectWithPriorityType::PRICE_LIST_FIELD];
            $priority = $submittedItem[PriceListSelectWithPriorityType::PRIORITY_FIELD];
            $mergeAllowed = $submittedItem[PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD];

            if (!$priceList instanceof PriceList) {
                continue;
            }

            $existingItem = $this->findExistingItemByPriceList($priceList, $existing);
            if ($existingItem) {
                if ($existingItem->getPriority() !== $priority
                    || $existingItem->isMergeAllowed() !== $mergeAllowed
                ) {
                    $existingItem->setPriority($priority);
                    $existingItem->setMergeAllowed($mergeAllowed);
                    $hasChanges = true;
                }
            } else {
                $relation = $this->createRelation($targetEntity);
                $relation->setWebsite($website)
                    ->setPriority($priority)
                    ->setMergeAllowed($mergeAllowed)
                    ->setPriceList($priceList);
                $this->em->persist($relation);
                $hasChanges = true;
            }

        }

        return $hasChanges;
    }

    /**
     * @param $targetEntity
     * @param Website $website
     * @return BasePriceListRelation
     */
    protected function createRelation($targetEntity, Website $website = null)
    {
        if ($targetEntity instanceof Account) {
            $relation = new PriceListToAccount();
            $relation->setAccount($targetEntity);
            return $relation;
        }

        if ($targetEntity instanceof AccountGroup) {
            $relation = new PriceListToAccountGroup();
            $relation->setAccountGroup($targetEntity);
            return $relation;
        }

        if ($targetEntity instanceof Website) {
            $relation = new PriceListToWebsite();
            return $relation;
        }

        return null;
    }

    /**
     * @param PriceList $priceList
     * @param array|BasePriceListRelation[] $existing
     * @return BasePriceListRelation
     */
    protected function findExistingItemByPriceList(PriceList $priceList, array $existing)
    {
        foreach ($existing as $existingItem) {
            if ($existingItem->getPriceList() === $priceList) {
                return $existingItem;
            }
        }

        return null;
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

    /**
     * @return string
     */
    protected function getRelationClass()
    {
        return $this->relationClass;
    }
}
