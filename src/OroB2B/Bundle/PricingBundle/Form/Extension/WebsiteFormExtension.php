<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListCollectionType;

class WebsiteFormExtension extends AbstractTypeExtension
{
    const PRICE_LISTS_TO_WEBSITE_FIELD = 'priceList';

    /** @var string */
    protected $priceListToWebsiteClass;

    /** @var  EntityManagerInterface */
    protected $entityManager;

    /** @var  RegistryInterface */
    protected $doctrine;

    /**
     * WebSiteFormExtension constructor.
     * @param RegistryInterface $doctrine
     * @param string $priceListToWebsiteClass
     */
    public function __construct(RegistryInterface $doctrine, $priceListToWebsiteClass)
    {
        $this->doctrine = $doctrine;
        $this->priceListToWebsiteClass = $priceListToWebsiteClass;
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::PRICE_LISTS_TO_WEBSITE_FIELD, PriceListCollectionType::NAME, [
                'allow_add_after' => false,
                'allow_add' => true,
                'required' => false
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'onPostSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return WebsiteType::NAME;
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Website|null $product */
        $website = $event->getData();

        if (!$website || !$website->getId()) {
            return;
        }

        $priceListsToWebsite = $this->getPriceListToWebsiteSaved($website);
        $data = [];
        foreach ($priceListsToWebsite as $entity) {
            $data[] = [
                'priceList' => $entity->getPriceList(),
                'priority' => $entity->getPriority(),
                'mergeAllowed' => $entity->isMergeAllowed(),
            ];
        }
        $event->getForm()->get(self::PRICE_LISTS_TO_WEBSITE_FIELD)->setData($data);
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $website = $event->getData();
        $form = $event->getForm();
        if (!$website || !$form->isValid()) {
            return;
        }

        $submitted = (array)$form->get(self::PRICE_LISTS_TO_WEBSITE_FIELD)->getData();
        $existing = $this->getPriceListToWebsiteSaved($website);

        $this->removeDeletedRelations($submitted, $existing);
        $this->persistSubmitted($submitted, $existing, $website);
    }

    /**
     * @param array $submitted
     * @param PriceListToWebsite[] $existing
     */
    protected function removeDeletedRelations(array $submitted, array $existing)
    {
        $submittedIds = array_map(function ($item) {
            /** @var PriceList $priceList */
            $priceList = $item['priceList'];
            if ($priceList instanceof PriceList) {
                return $priceList->getId();
            }
            return null;
        }, $submitted);

        $removed = array_diff(array_keys($existing), $submittedIds);

        foreach ($removed as $id) {
            $this->getPriceListToWebsiteManager()->remove($existing[$id]);
        }
    }

    /**
     * @param array $submitted
     * @param PriceListToWebsite[] $existing
     * @param Website $website
     */
    protected function persistSubmitted(array $submitted, array $existing, Website $website)
    {
        $ids = array_keys($existing);
        foreach ($submitted as $item) {
            $priceList = $item['priceList'];
            if (!$priceList instanceof PriceList) {
                continue;
            }
            if (in_array($priceList->getId(), $ids, true)) {
                $existing[$priceList->getId()]->setPriority($item['priority']);
                $existing[$priceList->getId()]->setMergeAllowed($item['mergeAllowed']);
            } else {
                $entity = new PriceListToWebsite();
                $entity->setWebsite($website)
                    ->setPriority($item['priority'])
                    ->setMergeAllowed($item['mergeAllowed'])
                    ->setPriceList($priceList);
                $this->getPriceListToWebsiteManager()->persist($entity);
            }
        }
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|null
     */
    protected function getPriceListToWebsiteManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->doctrine->getManagerForClass($this->priceListToWebsiteClass);
        }

        return $this->entityManager;
    }

    /**
     * @param Website $website
     * @return array|\OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite[]
     */
    protected function getPriceListToWebsiteSaved(Website $website)
    {
        $result = [];
        /** @var PriceListToWebsite[] $entities */
        $entities = $this->getPriceListToWebsiteManager()
            ->getRepository($this->priceListToWebsiteClass)
            ->findBy(['website' => $website], ['priority' => Criteria::ASC]);

        foreach ($entities as $entity) {
            $result[$entity->getPriceList()->getId()] = $entity;
        }

        return $result;
    }
}
