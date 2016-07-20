<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductEntityListener
{
    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldProvider;

    /**
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     * @param ManagerRegistry $registry
     * @param PriceRuleFieldsProvider $fieldProvider
     */
    public function __construct(
        ExtraActionEntityStorageInterface $extraActionsStorage,
        ManagerRegistry $registry,
        PriceRuleFieldsProvider $fieldProvider
    ) {
        $this->extraActionsStorage = $extraActionsStorage;
        $this->registry = $registry;
        $this->fieldProvider = $fieldProvider;
    }

    /**
     * @param Product $product
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Product $product, PreUpdateEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($product);

        $changedAttributes = $this->getChangedAttributes($changeSet);
        $changedRules = $this->findRulesByAttributes($changedAttributes);
        $priceLists = $this->getPriceListsForRecalculate($changedRules);
        
        $existingTriggers = $this->registry->getManagerForClass(PriceRuleChangeTrigger::class)
            ->getRepository(PriceRuleChangeTrigger::class)
            ->findBy(['product' => $product]);

        $existingTriggersPriceLists = array_map(
            function (PriceRuleChangeTrigger $item) {
                return $item->getPriceList();
            },
            $existingTriggers
        );

        $priceLists = array_diff($priceLists, $existingTriggersPriceLists);

        foreach ($priceLists as $priceList) {
            $trigger = new PriceRuleChangeTrigger($priceList, $product);

            if (!$this->isTriggerExistingInSchedule($trigger)) {
                $this->extraActionsStorage->scheduleForExtraInsert($trigger);
            }
        }
    }

    /**
     * @param PriceRuleChangeTrigger $trigger
     * @return bool
     */
    protected function isTriggerExistingInSchedule(PriceRuleChangeTrigger $trigger)
    {
        $existing = false;
        $scheduledForInsert = $this->extraActionsStorage->getScheduledForInsert();
        $triggerPriceList = $trigger->getPriceList();

        foreach ($scheduledForInsert as $item) {
            if ($item instanceof PriceRuleChangeTrigger && $item->getPriceList() == $triggerPriceList) {
                $existing = true;
            }
        }

        return $existing;
    }

    /**
     * @param array $changeSet
     * @return array
     */
    protected function getChangedAttributes(array $changeSet)
    {
        $supportedAttributes = [];
        
        $changedAttributes = array_keys($changeSet);
        $productFields = $this->fieldProvider->getFields(Product::class, false, true);
        
        foreach ($changedAttributes as $changedAttribute) {
            if (in_array($changedAttribute, $productFields)) {
                $supportedAttributes[] = $changedAttribute;
            }
        }

        return $supportedAttributes;
    }

    /**
     * @param array $attributes
     * @return PriceRule[]
     */
    protected function findRulesByAttributes(array $attributes)
    {
        $lexemes = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceRuleLexeme')
            ->getRepository('OroB2BPricingBundle:PriceRuleLexeme')
            ->findBy(['fieldName' => $attributes]);

        $rules = [];
        foreach ($lexemes as $lexeme) {
            $rules[] = $lexeme->getPriceRule();
        }

        return $rules;
    }

    /**
     * @param PriceRule[] $rules
     * @return PriceList[]
     */
    protected function getPriceListsForRecalculate(array $rules)
    {
        $priceLists = [];
        foreach ($rules as $rule) {
            $priceLists[] = $rule->getPriceList();
        }

        return array_unique($priceLists);
    }
}
