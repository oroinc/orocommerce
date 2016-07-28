<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractRuleEntityListener
{
    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldProvider;

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
     * @return string
     */
    abstract protected function getEntityClassName();

    /**
     * @param PriceRuleLexeme[] $lexemes
     * @param Product|null $product
     */
    protected function addTriggersByLexemes(array $lexemes, Product $product = null)
    {
        $priceLists = [];

        foreach ($lexemes as $lexeme) {
            $priceList = $lexeme->getPriceList();
            $priceLists[$priceList->getId()] = $priceList;
        }

        foreach ($priceLists as $priceList) {
            if (!$this->isExistingTriggerWithPriseList($priceList)) {
                $trigger = new PriceRuleChangeTrigger($priceList, $product);
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
     * @param null|int $relationId
     * @return array|\OroB2B\Bundle\PricingBundle\Entity\PriceRuleLexeme[]
     */
    protected function findEntityLexemes(array $updatedFields = [], $relationId = null)
    {
        $criteria = ['className' => $this->getEntityClassName()];
        if ($updatedFields) {
            $criteria['fieldName'] = $updatedFields;
        }
        if ($relationId) {
            $criteria['relationId'] = $relationId;
        }
        $lexemes = $this->registry->getManagerForClass(PriceRuleLexeme::class)
            ->getRepository(PriceRuleLexeme::class)
            ->findBy($criteria);

        return $lexemes;
    }

    /**
     * @param array $changeSet
     * @param Product $product
     */
    protected function recalculateByEntityFieldsUpdate(array $changeSet, Product $product = null)
    {
        $fields = $this->getEntityFields();
        $updatedFields = array_intersect($fields, array_keys($changeSet));

        if ($updatedFields) {
            $lexemes = $this->findEntityLexemes($updatedFields);
            $this->addTriggersByLexemes($lexemes, $product);
        }
    }

    protected function recalculateByEntity()
    {
        $lexemes = $this->findEntityLexemes();
        $this->addTriggersByLexemes($lexemes);
    }

    /**
     * @return array
     */
    protected function getEntityFields()
    {
        return $this->fieldsProvider->getFields($this->getEntityClassName(), false, true);
    }
}
