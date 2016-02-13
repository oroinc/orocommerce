<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceListFallback;
use OroB2B\Bundle\PricingBundle\Event\PriceListQueueChangeEvent;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepositoryInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractWebsiteScopedPriceListsType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var  EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @param object $targetEntity
     * @return BasePriceListRelation
     */
    abstract protected function createPriceListToTargetEntity($targetEntity);

    /**
     * @return string
     */
    abstract protected function getClassName();

    /**
     * @return string
     */
    abstract protected function getTargetFieldName();

    /**
     * @return array
     */
    abstract protected function getFallbackChoices();

    /**
     * @return string
     */
    abstract protected function getFallbackClassName();

    /**
     * @return string
     */
    abstract protected function getDefaultFallback();

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ManagerRegistry $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'], 10);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => PriceListsSettingsType::NAME,
                'options' => [
                    'fallback_class_name' => $this->getFallbackClassName(),
                    'target_field_name' => $this->getTargetFieldName(),
                    'fallback_choices' => $this->getFallbackChoices(),
                    'default_fallback' => $this->getDefaultFallback(),
                ],
                'label' => false,
                'required' => false,
                'mapped' => false,
                'data' => [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WebsiteScopedDataType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var object|null $targetEntity */
        $targetEntity = $form->getRoot()->getData();

        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        $formData = $this->prepareFormData($targetEntity, $form);
        $event->setData($formData);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        /** @var object|null $targetEntity */
        $targetEntity = $form->getRoot()->getData();
        if (!$targetEntity) {
            return;
        }
        if (!$form->isValid()) {
            return;
        }

        $em = $this->getEntityManager();
        /** @var FormInterface $priceListsByWebsites */
        $priceListsByWebsites = $form->getParent()->get('priceListsByWebsites');

        $fallbacks = $this->registry
            ->getManagerForClass($this->getFallbackClassName())
            ->getRepository($this->getFallbackClassName())
            ->findBy([$this->getTargetFieldName() => $targetEntity]);
        foreach ($priceListsByWebsites->all() as $priceListsByWebsite) {
            $website = $priceListsByWebsite->getConfig()->getOption('website');
            $submittedFallback = (int)$priceListsByWebsite->get('fallback')->getData();
            $actualFallback = $this->getFallbackByWebsite($fallbacks, $website);
            $hasFallbackChanges = $this->hasFallbackChanges($actualFallback, $submittedFallback);
            $actualPriceListsToTargetEntity = $this->getActualPriceListsToTargetEntity($targetEntity, $website);

            $submittedPriceLists = $this->getWebsiteSubmittedPriceLists($priceListsByWebsite);
            $hasRemoveChanges = $this->removePriceListRelations(
                $actualPriceListsToTargetEntity,
                $submittedPriceLists,
                $em
            );

            $priceListsWithPriority = $priceListsByWebsite
                ->get(PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD)
                ->all();

            $hasUpdateChanges = $this->updatePriceListsRelations(
                $priceListsWithPriority,
                $em,
                $targetEntity,
                $website,
                $actualPriceListsToTargetEntity
            );
            if ($hasRemoveChanges || $hasUpdateChanges || $hasFallbackChanges) {
                $this->eventDispatcher->dispatch(
                    PriceListQueueChangeEvent::BEFORE_CHANGE,
                    new PriceListQueueChangeEvent($targetEntity, $website)
                );
            }
        }
    }

    /**
     * @param PriceListFallback[] $fallbacks
     * @param Website $website
     * @return PriceListFallback|null
     */
    protected function getFallbackByWebsite($fallbacks, Website $website)
    {
        foreach ($fallbacks as $fallback) {
            if ($fallback->getWebsite()->getId() === $website->getId()) {
                return $fallback;
            }
        }

        return null;
    }

    /**
     * @param ObjectManager $em
     * @param object $targetEntity
     * @param Website $website
     * @param array $priceListWithPriorityData
     * @param array $actualPriceListsToTargetEntity
     * @return bool
     */
    protected function updatePriceListToTargetEntity(
        ObjectManager $em,
        $targetEntity,
        Website $website,
        array $priceListWithPriorityData,
        array $actualPriceListsToTargetEntity
    ) {
        $priceList = $priceListWithPriorityData[PriceListSelectWithPriorityType::PRICE_LIST_FIELD];
        if (!$priceList instanceof PriceList) {
            return false;
        }
        if (in_array($priceList->getId(), array_keys($actualPriceListsToTargetEntity), true)) {
            /** @var BasePriceListRelation $priceListToTargetEntity */
            $priceListToTargetEntity = $actualPriceListsToTargetEntity[$priceList->getId()];
            $hasChanges = $priceListToTargetEntity->getPriority() !== (int)$priceListWithPriorityData['priority']
                || $priceListToTargetEntity->isMergeAllowed() !== $priceListWithPriorityData['mergeAllowed'];
        } else {
            $priceListToTargetEntity = $this->createPriceListToTargetEntity($targetEntity);
            $priceListToTargetEntity->setWebsite($website);
            $priceListToTargetEntity
                ->setPriceList($priceListWithPriorityData[PriceListSelectWithPriorityType::PRICE_LIST_FIELD]);
            $hasChanges = true;
        }
        $priceListToTargetEntity
            ->setPriority($priceListWithPriorityData[PriceListSelectWithPriorityType::PRIORITY_FIELD]);
        $priceListToTargetEntity
            ->setMergeAllowed($priceListWithPriorityData[PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD]);
        $em->persist($priceListToTargetEntity);

        return $hasChanges;
    }

    /**
     * @param object $targetEntity
     * @param Website $website
     * @return BasePriceListRelation[]
     */
    protected function getActualPriceListsToTargetEntity($targetEntity, Website $website)
    {
        $repo = $this->getRepository();
        if (!$targetEntity->getId()) {
            $actualPriceListsToTargetEntity = [];
        } else {
            $actualPriceListsToTargetEntity =
                $repo->getPriceLists($targetEntity, $website, PriceListCollectionType::DEFAULT_ORDER);
        }

        $result = [];
        foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
            $priceListId = $priceListToTargetEntity->getPriceList()->getId();
            $result[$priceListId] = $priceListToTargetEntity;
        }

        return $result;
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
     * @param object $targetEntity
     * @param FormInterface $priceListsByWebsites
     * @return array
     */
    protected function prepareFormData($targetEntity, FormInterface $priceListsByWebsites)
    {
        $formData = [];
        $repo = $this->getRepository();
        foreach ($priceListsByWebsites->all() as $priceListsByWebsite) {
            /** @var Website $website */
            $website = $priceListsByWebsite->getConfig()->getOption('website');
            $actualPriceListsToTargetEntity =
                $repo->getPriceLists($targetEntity, $website, PriceListCollectionType::DEFAULT_ORDER);

            $actualPriceLists = [];
            /** @var object $priceListToTargetEntity */
            foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
                $priceLists[PriceListSelectWithPriorityType::PRICE_LIST_FIELD] =
                    $priceListToTargetEntity->getPriceList();
                $priceLists[PriceListSelectWithPriorityType::PRIORITY_FIELD] = $priceListToTargetEntity->getPriority();
                $priceLists[PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD] =
                    $priceListToTargetEntity->isMergeAllowed();

                $actualPriceLists[] = $priceLists;
            }

            $formData[$website->getId()][PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD] = $actualPriceLists;
        }

        return $formData;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->registry->getManagerForClass($this->getClassName());
        }

        return $this->entityManager;
    }

    /**
     * @return PriceListRepositoryInterface
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->getEntityManager()->getRepository($this->getClassName());
        }

        return $this->repository;
    }

    /**
     * @param BasePriceListRelation[] $actualPriceListsToTargetEntity
     * @param array $submittedPriceLists
     * @param EntityManager $em
     * @return bool
     */
    protected function removePriceListRelations(
        $actualPriceListsToTargetEntity,
        $submittedPriceLists,
        EntityManager $em
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
     * @param FormInterface[] $priceListsWithPriority
     * @param EntityManager $em
     * @param object $targetEntity
     * @param Website $website
     * @param BasePriceListRelation[] $actualPriceListsToTargetEntity
     * @return bool
     */
    protected function updatePriceListsRelations(
        $priceListsWithPriority,
        $em,
        $targetEntity,
        Website $website,
        $actualPriceListsToTargetEntity
    ) {
        $hasChanges = false;
        foreach ($priceListsWithPriority as $priceListWithPriority) {
            $priceListWithPriorityData = $priceListWithPriority->getData();
            $hasUpdateChanges = $this->updatePriceListToTargetEntity(
                $em,
                $targetEntity,
                $website,
                $priceListWithPriorityData,
                $actualPriceListsToTargetEntity
            );
            $hasChanges = $hasChanges || $hasUpdateChanges;
        }

        return $hasChanges;
    }

    /**
     * @param PriceListFallback $actualFallback
     * @param integer $submittedFallback
     * @return bool
     */
    protected function hasFallbackChanges($actualFallback, $submittedFallback)
    {
        $hasFallbackChanges = (!$actualFallback && $submittedFallback !== $this->getDefaultFallback())
            || ($actualFallback && $submittedFallback !== $actualFallback->getFallback());

        return $hasFallbackChanges;
    }
}
