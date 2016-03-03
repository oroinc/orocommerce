<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\PricingBundle\Form\PriceListWithPriorityCollectionHandler;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountGroupFormExtension extends AbstractTypeExtension
{
    const PRICE_LISTS_BY_WEBSITES = 'priceListsByWebsites';

    /**
     * @var PriceListWithPriorityCollectionHandler
     */
    protected $collectionHandler;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var array
     */
    protected $existingRelations = [];

    /**
     * @var string
     */
    protected $relationClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup';

    /**
     * @var string
     */
    protected $fallbackClass = 'OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback';


    /**
     * @param PriceListWithPriorityCollectionHandler $collectionHandler
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListChangeTriggerHandler $triggerHandler
     */
    public function __construct(
        PriceListWithPriorityCollectionHandler $collectionHandler,
        DoctrineHelper $doctrineHelper,
        PriceListChangeTriggerHandler $triggerHandler
    ) {
        $this->collectionHandler = $collectionHandler;
        $this->doctrineHelper = $doctrineHelper;
        $this->triggerHandler = $triggerHandler;
    }


    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AccountGroupType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::PRICE_LISTS_BY_WEBSITES,
            WebsiteScopedDataType::NAME,
            [
                'type' => PriceListsSettingsType::NAME,
                'options' => [
                    PriceListsSettingsType::PRICE_LIST_RELATION_CLASS => $this->relationClass,
                    PriceListsSettingsType::FALLBACK_CHOICES => $this->getFallbackChoices(),
                ],
                'label' => false,
                'required' => false,
                'mapped' => false,
                'data' => [],
            ]
        );

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $event->getForm()->getData();
        if (!$accountGroup || !$accountGroup->getId()) {
            return;
        }

        foreach ($event->getForm()->get(self::PRICE_LISTS_BY_WEBSITES)->all() as $form) {
            $website = $form->getConfig()->getOption(WebsiteScopedDataType::WEBSITE_OPTION);
            $existing = $this->getExistingRelations($accountGroup, $website);
            $fallback = $this->getFallback($website, $accountGroup);
            $form->get(PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD)->setData($existing);
            $form->get(PriceListsSettingsType::FALLBACK_FIELD)->setData($fallback->getFallback());
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $accountGroup = $event->getForm()->getData();
        foreach ($event->getForm()->get(self::PRICE_LISTS_BY_WEBSITES)->all() as $form) {
            $data = $form->getData();
            $website = $form->getConfig()->getOption(WebsiteScopedDataType::WEBSITE_OPTION);
            $existing = $this->getExistingRelations($accountGroup, $website);

            $submitted = $data[PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD];
            $hasChanges = $this->collectionHandler->handleChanges($submitted, $existing, $accountGroup, $website);

            $fallback = $this->getFallback($website, $accountGroup) ?: $this->createFallback($accountGroup, $website);
            $fallbackData = $form->get(PriceListsSettingsType::FALLBACK_FIELD)->getData();
            if ($fallbackData !== $fallback->getFallback()) {
                $fallback->setFallback($fallbackData);
                $hasChanges = true;
            }

            if ($hasChanges) {
                $this->triggerHandler->handleAccountGroupChange($accountGroup, $website);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getFallbackChoices()
    {
        return [
            PriceListAccountGroupFallback::WEBSITE =>
                'orob2b.pricing.fallback.website.label',
            PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                'orob2b.pricing.fallback.current_account_group_only.label',
        ];
    }


    /**
     * @param Website $website
     * @param object $targetEntity
     * @return PriceListAccountGroupFallback
     */
    protected function getFallback(Website $website, $targetEntity)
    {
        return $this->doctrineHelper->getEntityRepository($this->fallbackClass)
            ->findOneBy(
                [
                    'accountGroup' => $targetEntity,
                    'website' => $website,
                ]
            );
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return array|PriceListToAccountGroup[]
     */
    protected function getExistingRelations(AccountGroup $accountGroup, Website $website)
    {
        if (!$accountGroup->getId()) {
            return [];
        }

        $key = spl_object_hash($accountGroup) . '_' . spl_object_hash($website);
        if (!array_key_exists($key, $this->existingRelations)) {
            /** @var PriceListToAccountGroupRepository $entityRepository */
            $entityRepository = $this->doctrineHelper
                ->getEntityRepository($this->relationClass);
            $this->existingRelations[$key] = $entityRepository
                ->getPriceLists($accountGroup, $website, PriceListCollectionType::DEFAULT_ORDER);
        }

        return $this->existingRelations[$key];
    }

    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @return PriceListAccountGroupFallback
     */
    protected function createFallback(AccountGroup $accountGroup, Website $website)
    {
        $fallback = new PriceListAccountGroupFallback();
        $fallback->setAccountGroup($accountGroup)
            ->setWebsite($website);
        $this->doctrineHelper->getEntityManager($fallback)
            ->persist($fallback);

        return $fallback;
    }
}
