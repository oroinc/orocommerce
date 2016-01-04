<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
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
            ->add(
                self::PRICE_LISTS_TO_WEBSITE_FIELD,
                PriceListCollectionType::NAME,
                [
                    'allow_add_after' => false,
                    'allow_add' => true,
                    'required' => false,
                ]
            )
            ->add(
                'fallback',
                'choice',
                [
                    'label' => 'orob2b.pricing.fallback.label',
                    'mapped' => false,
                    'choices' => [
                        PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY =>
                            'orob2b.pricing.fallback.current_website_only.label',
                        PriceListWebsiteFallback::CONFIG =>
                            'orob2b.pricing.fallback.config.label',
                    ],
                ]
            );

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

        $data = [];
        foreach ($this->getPriceListToWebsiteSaved($website) as $entity) {
            $data[] = [
                'priceList' => $entity->getPriceList(),
                'priority' => $entity->getPriority(),
                'mergeAllowed' => $entity->isMergeAllowed(),
            ];
        }
        $event->getForm()->get(self::PRICE_LISTS_TO_WEBSITE_FIELD)->setData($data);
        $fallback = $this->getFallback($website);
        $fallbackField = $event->getForm()->get('fallback');
        if (!$fallback || $fallback->getFallback() === PriceListWebsiteFallback::CONFIG) {
            $fallbackField->setData(PriceListWebsiteFallback::CONFIG);
        } else {
            $fallbackField->setData(PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY);
        }
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

        $fallback = $this->getFallback($website);
        if (!$fallback) {
            $fallback = new PriceListWebsiteFallback();
            $this->doctrine->getManagerForClass('OroB2BPricingBundle:PriceListWebsiteFallback')->persist($fallback);
        }
        $fallback->setWebsite($website);
        $fallback->setFallback($form->get('fallback')->getData());
    }

    /**
     * @param array $submitted
     * @param PriceListToWebsite[] $existing
     */
    protected function removeDeletedRelations(array $submitted, array $existing)
    {
        $submittedIds = array_map(
            function ($item) {
                /** @var PriceList $priceList */
                $priceList = $item['priceList'];
                if ($priceList instanceof PriceList) {
                    return $priceList->getId();
                }

                return null;
            },
            $submitted
        );

        $removed = array_diff(array_keys($existing), $submittedIds);

        foreach ($removed as $id) {
            $this->getPriceListToWebsiteManager()->remove($existing[$id]);
        }
    }

    /**
     * @param Website $website
     * @return null|PriceListWebsiteFallback
     */
    protected function getFallback(Website $website)
    {
        return $this->doctrine
            ->getManagerForClass('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->getRepository('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->findOneBy(['website' => $website]);
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
