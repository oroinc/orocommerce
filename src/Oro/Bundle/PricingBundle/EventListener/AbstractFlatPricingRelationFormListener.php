<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Symfony\Component\Form\FormEvent;

/**
 * Load and Save price list to entity relation when flat pricing storage enabled.
 */
abstract class AbstractFlatPricingRelationFormListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListRelationTriggerHandler
     */
    protected $triggerHandler;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListRelationTriggerHandler $triggerHandler
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param Website $website
     * @param object $targetEntity
     * @return BasePriceListRelation|null
     */
    abstract protected function getPriceListRelation(Website $website, $targetEntity): ?BasePriceListRelation;

    /**
     * @param Website $website
     * @param Customer|CustomerGroup $targetEntity
     * @return BasePriceListRelation
     */
    abstract protected function createNewRelation(Website $website, $targetEntity): BasePriceListRelation;

    /**
     * @param Website $website
     * @param Customer|CustomerGroup $targetEntity
     */
    abstract protected function handlePriceListChanges(Website $website, $targetEntity);

    public function onPostSetData(FormEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $targetEntity = $event->getForm()->getData();
        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        foreach ($event->getForm()->get('priceListsByWebsites')->all() as $form) {
            /** @var Website $website */
            $website = $form->getConfig()->getOption(WebsiteScopedDataType::WEBSITE_OPTION);

            $priceListRelation = $this->getPriceListRelation($website, $targetEntity);
            $priceList = $priceListRelation ? $priceListRelation->getPriceList() : null;
            $form->get('priceList')->setData($priceList);
        }
    }

    public function onPostSubmit(AfterFormProcessEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $targetEntity = $event->getForm()->getData();
        foreach ($event->getForm()->get('priceListsByWebsites')->all() as $form) {
            /** @var Website $website */
            $website = $form->getConfig()->getOption(WebsiteScopedDataType::WEBSITE_OPTION);
            /** @var PriceList $priceList */
            $priceList = $form->get('priceList')->getData();

            $priceListRelation = null;
            if ($targetEntity && $targetEntity->getId()) {
                $priceListRelation = $this->getPriceListRelation($website, $targetEntity);
            }

            if ($priceList) {
                $this->handlePriceListRelationUpdate($website, $targetEntity, $priceListRelation, $priceList);
            } elseif ($priceListRelation) {
                $this->handlePriceListRelationRemove($website, $targetEntity, $priceListRelation);
            }
        }
    }

    /**
     * @param Website $website
     * @param Customer|CustomerGroup $targetEntity
     * @param BasePriceListRelation $priceListRelation
     * @throws \Doctrine\ORM\ORMException
     */
    protected function handlePriceListRelationRemove(
        Website $website,
        $targetEntity,
        BasePriceListRelation $priceListRelation
    ): void {
        $this->doctrineHelper->getEntityManager($priceListRelation)->remove($priceListRelation);
        $this->handlePriceListChanges($website, $targetEntity);
    }

    /**
     * @param Website $website
     * @param Customer|CustomerGroup $targetEntity
     * @param BasePriceListRelation|null $priceListRelation
     * @param PriceList $priceList
     * @throws \Doctrine\ORM\ORMException
     */
    protected function handlePriceListRelationUpdate(
        Website $website,
        $targetEntity,
        ?BasePriceListRelation $priceListRelation,
        PriceList $priceList
    ): void {
        if (!$priceListRelation) {
            $priceListRelation = $this->createNewRelation($website, $targetEntity);
        }

        $hasChanges = !($priceListRelation->getPriceList()
            && $priceListRelation->getPriceList()->getId() === $priceList->getId());
        $priceListRelation->setPriceList($priceList);
        $this->doctrineHelper->getEntityManager($priceListRelation)->persist($priceListRelation);

        if ($hasChanges) {
            $this->handlePriceListChanges($website, $targetEntity);
        }
    }
}
