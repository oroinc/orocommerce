<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\PricingBundle\Entity\PriceListFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepositoryInterface;
use Oro\Bundle\PricingBundle\Form\PriceListWithPriorityCollectionHandler;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * Adds existing price lists and fallback if exists on post set data
 * Creates fallback if missing and data differs from default
 * and triggers collection changes handler if there are any changes
 */
abstract class AbstractPriceListCollectionAwareListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const PRICE_LISTS_COLLECTION_FORM_FIELD_NAME = 'priceListsByWebsites';

    /**
     * @var PriceListWithPriorityCollectionHandler
     */
    protected $collectionHandler;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListRelationTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var array
     */
    protected $existingRelations = [];

    /**
     * @var array|PriceListFallback[]
     */
    protected $fallbacks = [];

    public function __construct(
        PriceListWithPriorityCollectionHandler $collectionHandler,
        DoctrineHelper $doctrineHelper,
        PriceListRelationTriggerHandler $triggerHandler
    ) {
        $this->collectionHandler = $collectionHandler;
        $this->doctrineHelper = $doctrineHelper;
        $this->triggerHandler = $triggerHandler;
    }

    public function onPostSetData(FormEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        /** @var CustomerGroup|Customer $targetEntity */
        $targetEntity = $event->getForm()->getData();
        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        foreach ($event->getForm()->get(self::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME)->all() as $form) {
            $website = $form->getConfig()->getOption(WebsiteScopedDataType::WEBSITE_OPTION);
            $existing = $this->getExistingRelations($targetEntity, $website);
            $form->get(PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD)->setData($existing);

            $fallback = $this->getFallback($website, $targetEntity);
            if ($fallback) {
                $form->get(PriceListsSettingsType::FALLBACK_FIELD)->setData($fallback->getFallback());
            }
        }
    }

    public function onPostSubmit(AfterFormProcessEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $targetEntity = $event->getForm()->getData();
        foreach ($event->getForm()->get(self::PRICE_LISTS_COLLECTION_FORM_FIELD_NAME)->all() as $form) {
            $data = $form->getData();
            $website = $form->getConfig()->getOption(WebsiteScopedDataType::WEBSITE_OPTION);
            $existingRelations = $this->getExistingRelations($targetEntity, $website);

            $submitted = $data[PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD];
            $hasChanges = $this->collectionHandler
                ->handleChanges($submitted, $existingRelations, $targetEntity, $website);

            if ($this->processFallbackSubmit($targetEntity, $website, $form)) {
                $hasChanges = true;
            }

            if ($hasChanges) {
                $this->handleCollectionChanges($targetEntity, $website);
            }
        }
    }

    /**
     * @param CustomerGroup|Customer $targetEntity
     * @param Website $website
     * @return array|PriceListToCustomerGroup[]|PriceListToCustomer[]
     */
    protected function getExistingRelations($targetEntity, Website $website)
    {
        if (!$targetEntity->getId()) {
            return [];
        }

        $key = $this->getKey($targetEntity, $website);
        if (!array_key_exists($key, $this->existingRelations)) {
            /** @var PriceListRepositoryInterface $entityRepository */
            $entityRepository = $this->doctrineHelper
                ->getEntityRepository($this->getRelationClass());
            $this->existingRelations[$key] = $entityRepository
                ->getPriceLists($targetEntity, $website, PriceListCollectionType::DEFAULT_ORDER);
        }

        return $this->existingRelations[$key];
    }

    /**
     * @param $targetEntity
     * @param Website $website
     * @return string
     */
    protected function getKey($targetEntity, Website $website)
    {
        return spl_object_hash($targetEntity) . '_' . spl_object_hash($website);
    }

    /**
     * @param Website $website
     * @param CustomerGroup|Customer $targetEntity
     * @return PriceListFallback
     */
    protected function getFallback(Website $website, $targetEntity)
    {
        if (!$targetEntity->getId()) {
            return null;
        }

        if (!$this->fallbacks) {
            $fallbacks = $this->getFallbacks($targetEntity);
            foreach ($fallbacks as $fallback) {
                $this->fallbacks[spl_object_hash($fallback->getWebsite())] = $fallback;
            }
        }

        if (!array_key_exists(spl_object_hash($website), $this->fallbacks)) {
            $this->fallbacks[spl_object_hash($website)] = null;
        }

        return $this->fallbacks[spl_object_hash($website)];
    }

    /**
     * @param CustomerGroup|Customer $targetEntity
     * @return PriceListFallback[]
     */
    abstract protected function getFallbacks($targetEntity);

    /**
     * @param CustomerGroup|Customer $targetEntity
     * @param Website $website
     * @return PriceListFallback
     */
    abstract protected function createFallback($targetEntity, Website $website);

    /**
     * @return int
     */
    abstract protected function getDefaultFallback();

    /**
     * @return string
     */
    abstract protected function getRelationClass();

    /**
     * @param Customer|CustomerGroup $targetEntity
     * @param Website $website
     */
    abstract protected function handleCollectionChanges($targetEntity, Website $website);

    /**
     * @param CustomerGroup|Customer $targetEntity
     * @param Website $website
     * @param FormInterface $form
     * @return bool
     * @throws \Doctrine\ORM\ORMException
     */
    protected function processFallbackSubmit($targetEntity, Website $website, FormInterface $form): bool
    {
        $fallback = $this->getFallback($website, $targetEntity);
        $fallbackData = $form->get(PriceListsSettingsType::FALLBACK_FIELD)->getData();

        if (!$fallback && $fallbackData !== $this->getDefaultFallback()) {
            $fallback = $this->createFallback($targetEntity, $website);
            $this->doctrineHelper->getEntityManager($fallback)
                ->persist($fallback);
        }

        if ($fallback && $fallbackData !== $fallback->getFallback()) {
            $fallback->setFallback($fallbackData);

            return true;
        }

        return false;
    }
}
