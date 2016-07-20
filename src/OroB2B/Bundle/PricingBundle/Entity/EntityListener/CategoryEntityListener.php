<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;

class CategoryEntityListener
{
    /**
     * @var array
     */
    protected $handledCategories = [];

    /**
     * @var array
     */
    protected $handledPriceLists = [];

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param PriceRuleFieldsProvider $fieldsProvider
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        PriceRuleFieldsProvider $fieldsProvider
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        $fields = $this->fieldsProvider->getFields(Category::class);
        $x = 1;
    }

    /**
     * @param Category $category
     * @param LifecycleEventArgs $event
     */
    public function preRemove(Category $category, LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $categoryRepository = $em->getRepository(Category::class);

        /** @var Category[] $categories */
        $categories = array_merge($categoryRepository->getPath($category), $categoryRepository->getChildren($category));

        $ids = [];
        foreach ($categories as $category) {
            $id = $category->getId();
            if (!in_array($id, $this->handledCategories)) {
                $this->handledCategories[] = $id;
                $ids[] = $id;
            }
        }

        if ($ids) {
            $lexemes = $em->getRepository(PriceRuleLexeme::class)->findBy(
                [
                    'className' => Category::class,
                    'relationId' => $ids,
                ]
            );
            foreach ($lexemes as $lexeme) {
                $priceList = $lexeme->getPriceList();
                if (!in_array($priceList, $this->handledPriceLists)) {
                    $this->handledPriceLists[] = $priceList;
                    $trigger = new PriceRuleChangeTrigger($priceList);
                    $this->extraActionsStorage->scheduleForExtraInsert($trigger);
                }
            }
        }
    }
}
