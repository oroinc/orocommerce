<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;

class CategoryEntityListener
{
    const FIELD_PARENT_CATEGORY = 'parentCategory';
    const FIELD_PRODUCTS = 'products';

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param PriceRuleFieldsProvider $fieldsProvider
     * @param RegistryInterface $registry
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        PriceRuleFieldsProvider $fieldsProvider,
        RegistryInterface $registry
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->fieldsProvider = $fieldsProvider;
        $this->registry = $registry;
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        // handle category tree changes
        if ($event->hasChangedField(self::FIELD_PARENT_CATEGORY)) {
            $categoryRepository = $this->getManager()->getRepository(Category::class);
            // handle both previous and new category paths
            $categories = array_merge(
                $categoryRepository->getPath($category),
                $categoryRepository->getPath($event->getOldValue(self::FIELD_PARENT_CATEGORY))
            );
            $this->createTriggersByCategories($categories);
        }

        $fields = $this->fieldsProvider->getFields(Category::class, false, true);
        // products and parent category already handled separately
        $fields = array_diff($fields, [self::FIELD_PARENT_CATEGORY, self::FIELD_PRODUCTS]);
        $updatedFields = array_intersect($fields, array_keys($event->getEntityChangeSet()));
        if ($updatedFields) {
            $lexemes = $this->getManager()
                ->getRepository(PriceRuleLexeme::class)
                ->findBy(
                    [
                        'className' => Category::class,
                        'relationId' => $category->getId(),
                        'fieldName' => $updatedFields,
                    ]
                );
            $this->addTriggersByLexemes($lexemes);
        }
    }

    /**
     * @param Category $category
     */
    public function preRemove(Category $category)
    {
        $categoryRepository = $this->getManager()->getRepository(Category::class);
        $categories = array_merge(
            $categoryRepository->getPath($category),
            $categoryRepository->getChildren($category)
        );
        $this->createTriggersByCategories($categories);
    }

    /**
     * @return ObjectManager|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass(Category::class);
    }

    /**
     * @param Category[] $categories
     */
    protected function createTriggersByCategories(array $categories)
    {
        $ids = [];
        foreach ($categories as $category) {
            $id = $category->getId();
            if (!in_array($id, $ids)) {
                $ids[] = $id;
            }
        }

        if ($ids) {
            $lexemes = $this->getManager()
                ->getRepository(PriceRuleLexeme::class)
                ->findBy(
                    [
                        'className' => Category::class,
                        'relationId' => $ids,
                    ]
                );
            $this->addTriggersByLexemes($lexemes);
        }
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     */
    protected function addTriggersByLexemes(array $lexemes)
    {
        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            if (!$this->isExistingTriggerWithPriseList($priceList)) {
                $trigger = new PriceRuleChangeTrigger($priceList);
                $this->extraActionsStorage->scheduleForExtraInsert($trigger);
            }
        }
    }

    /**
     * @param PriceList $priceList
     * @return bool
     */
    protected function isExistingTriggerWithPriseList(PriceList $priceList)
    {
        /** @var PriceRuleChangeTrigger[] $triggers */
        $triggers = $this->extraActionsStorage->getScheduledForInsert(PriceRuleChangeTrigger::class);
        foreach ($triggers as $trigger) {
            if ($trigger->getPriceList()->getId() == $priceList->getId()) {
                return true;
            }
        }

        return false;
    }
}
