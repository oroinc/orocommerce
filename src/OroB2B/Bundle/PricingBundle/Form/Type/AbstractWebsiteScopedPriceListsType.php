<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

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
                'type' => PriceListCollectionType::NAME,
                'label' => 'orob2b.pricing.pricelist.entity_plural_label',
                'required' => false,
                'mapped' => false,
                'ownership_disabled' => true,
                'data' => [],
                'preloaded_websites' => [],
            ]
        );
    }

    public function getParent()
    {
        return WebsiteScopedDataType::NAME;
    }

    /**
     * @return PriceListRepositoryInterface
     */
    abstract protected function getRepository();

    /**
     * @param object $targetEntity
     * @return BasePriceListRelation
     */
    abstract protected function createPriceListToTargetEntity($targetEntity);

    /**
     * @return ObjectManager
     */
    abstract protected function getEntityManager();

    /**
     * {@inheritdoc}
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm()->getParent();

        /** @var object|null $targetEntity */
        $targetEntity = $form->getData();

        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        $form = $event->getForm();

        /** @var FormInterface[] $priceListsByWebsites */
        $priceListsByWebsites = $form->getParent()->get('priceListsByWebsites');

        $formData = [];

        foreach ($priceListsByWebsites as $priceListsByWebsite) {
            $website = $priceListsByWebsite->getConfig()->getOption('website');
            $actualPriceListsToTargetEntity = $this->getRepository()
                ->getPriceLists($targetEntity, $website);

            $actualPriceLists = [];

            /** @var object $priceListToTargetEntity */
            foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
                $priceLists['priceList'] = $priceListToTargetEntity->getPriceList();
                $priceLists['priority'] = $priceListToTargetEntity->getPriority();

                $actualPriceLists[] = $priceLists;
            }

            $formData[$website->getId()] = $actualPriceLists;
        }

        $event->setData($formData);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var object|null $targetEntity */
        $targetEntity = $event->getForm()->getParent()->getData();
        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var FormInterface $priceListsByWebsites */
        $priceListsByWebsites = $form->getParent()->get('priceListsByWebsites');

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

            foreach ($priceListsByWebsite->all() as $priceListWithPriority) {
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
    )
    {
        /** @var PriceList $priceList */
        $priceList = $priceListWithPriorityData['priceList'];
        if (in_array($priceList->getId(), array_keys($actualPriceListsToTargetEntity))) {
            /** @var BasePriceListRelation $priceListToTargetEntity */
            $priceListToTargetEntity = $actualPriceListsToTargetEntity[$priceList->getId()];
            $priceListToTargetEntity->setPriority($priceListWithPriorityData['priority']);
        } else {
            $priceListToTargetEntity = $this->createPriceListToTargetEntity($targetEntity);
            $priceListToTargetEntity->setWebsite($website);
            $priceListToTargetEntity->setPriceList($priceListWithPriorityData['priceList']);
            $priceListToTargetEntity->setPriority($priceListWithPriorityData['priority']);
        }

        $em->persist($priceListToTargetEntity);
    }

    /**
     * @param object $targetEntity
     * @param Website $website
     * @return PriceList[]
     */
    protected function getActualPriceListsToTargetEntity($targetEntity, Website $website)
    {
        $actualPriceListsToTargetEntity = $this->getRepository()
            ->getPriceLists($targetEntity, $website);

        $result = [];
        /** @var BasePriceListRelation[] $actualPriceListsToTargetEntity */
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

        foreach ($priceListsByWebsite->getData() as $item) {
            $submittedPriceLists[] = $item['priceList'];
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
        foreach ($priceListsByWebsites->all() as $priceListsByWebsite) {
            /** @var Website $website */
            $website = $priceListsByWebsite->getConfig()->getOption('website');

            $actualPriceListsToTargetEntity = $this->getRepository()
                ->getPriceLists($targetEntity, $website);

            $actualPriceLists = [];

            /** @var object $priceListToTargetEntity */
            foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
                $priceLists['priceList'] = $priceListToTargetEntity->getPriceList();
                $priceLists['priority'] = $priceListToTargetEntity->getPriority();

                $actualPriceLists[] = $priceLists;
            }

            $formData[$website->getId()] = $actualPriceLists;
        }

        return $formData;
    }
}
