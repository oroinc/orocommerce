<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepositoryInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

abstract class AbstractWebsiteScopedPriceListsType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

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
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
        $parentForm = $event->getForm()->getParent();
        /** @var object|null $targetEntity */
        $targetEntity = $parentForm->getData();

        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        /** @var FormInterface $priceListsByWebsites */
        $priceListsByWebsites = $parentForm->get('priceListsByWebsites');

        $formData = $this->prepareFormData($targetEntity, $priceListsByWebsites);
        $event->setData($formData);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        $parentForm = $event->getForm()->getParent();
        /** @var object|null $targetEntity */
        $targetEntity = $parentForm->getData();
        if (!$targetEntity) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var FormInterface $priceListsByWebsites */
        $priceListsByWebsites = $parentForm->get('priceListsByWebsites');

        $em = $this->getEntityManager();

        foreach ($priceListsByWebsites->all() as $priceListsByWebsite) {
            $website = $priceListsByWebsite->getConfig()->getOption('website');
            $actualPriceListsToTargetEntity = $this->getActualPriceListsToTargetEntity($targetEntity, $website);

            $submittedPriceLists = $this->getWebsiteSubmittedPriceLists($priceListsByWebsite);

            /** @var BasePriceListRelation[] $actualPriceListsToTargetEntity */
            foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
                if (!in_array($priceListToTargetEntity->getPriceList(), $submittedPriceLists)) {
                    $em->remove($priceListToTargetEntity);
                }
            }
            $priceListsWithPriority = $priceListsByWebsite
                ->get(PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD)
                ->all();

            foreach ($priceListsWithPriority as $priceListWithPriority) {
                $priceListWithPriorityData = $priceListWithPriority->getData();
                $this->updatePriceListToTargetEntity(
                    $em,
                    $targetEntity,
                    $website,
                    $priceListWithPriorityData,
                    $actualPriceListsToTargetEntity
                );
            }
        }
    }

    /**
     * @param ObjectManager $em
     * @param object $targetEntity
     * @param Website $website
     * @param array $priceListWithPriorityData
     * @param array $actualPriceListsToTargetEntity
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
            return;
        }
        if (in_array($priceList->getId(), array_keys($actualPriceListsToTargetEntity))) {
            /** @var BasePriceListRelation $priceListToTargetEntity */
            $priceListToTargetEntity = $actualPriceListsToTargetEntity[$priceList->getId()];
        } else {
            $priceListToTargetEntity = $this->createPriceListToTargetEntity($targetEntity);
            $priceListToTargetEntity->setWebsite($website);
            $priceListToTargetEntity
                ->setPriceList($priceListWithPriorityData[PriceListSelectWithPriorityType::PRICE_LIST_FIELD]);
        }
        $priceListToTargetEntity
            ->setPriority($priceListWithPriorityData[PriceListSelectWithPriorityType::PRIORITY_FIELD]);
        $priceListToTargetEntity
            ->setMergeAllowed($priceListWithPriorityData[PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD]);
        $em->persist($priceListToTargetEntity);
    }

    /**
     * @param object $targetEntity
     * @param Website $website
     * @return PriceList[]
     */
    protected function getActualPriceListsToTargetEntity($targetEntity, Website $website)
    {
        $repo = $this->getRepository();
        $actualPriceListsToTargetEntity = !$targetEntity->getId() ? [] : $repo->getPriceLists($targetEntity, $website);

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
            $actualPriceListsToTargetEntity = $repo->getPriceLists($targetEntity, $website);

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
}
