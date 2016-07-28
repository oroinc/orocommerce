<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Symfony\Bridge\Doctrine\RegistryInterface;

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
        if ($event->hasChangedField(self::FIELD_PARENT_CATEGORY)) {
            // handle category tree changes
            $lexemes = $this->findLexemes();
            $this->addTriggersByLexemes($lexemes);
        } else {
            $fields = $this->fieldsProvider->getFields(Category::class, false, true);
            $updatedFields = array_intersect($fields, array_keys($event->getEntityChangeSet()));
            
            if ($updatedFields) {
                $lexemes = $this->findLexemes($updatedFields);
                $this->addTriggersByLexemes($lexemes);
            }
        }
    }

    public function preRemove()
    {
        $lexemes = $this->findLexemes();
        $this->addTriggersByLexemes($lexemes);
    }

    /**
     * @return ObjectManager|null
     */
    protected function getManager()
    {
        return $this->registry->getManagerForClass(Category::class);
    }

    /**
     * @param PriceRuleLexeme[] $lexemes
     */
    protected function addTriggersByLexemes(array $lexemes)
    {
        $priceLists = [];

        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            $priceLists[$priceList->getId()] = $priceList;
        }

        foreach ($priceLists as $priceList) {
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
            if ($trigger->getPriceList()->getId() === $priceList->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $updatedFields
     * @return array|\OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme[]
     */
    protected function findLexemes(array $updatedFields = [])
    {
        $criteria = [
            'className' => Category::class,
        ];
        if ($updatedFields) {
            $criteria['fieldName'] = $updatedFields;
        }
        $lexemes = $this->getManager()
            ->getRepository(PriceRuleLexeme::class)
            ->findBy($criteria);

        return $lexemes;
    }
}
