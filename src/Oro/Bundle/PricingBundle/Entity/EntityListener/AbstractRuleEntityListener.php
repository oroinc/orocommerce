<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Bridge\Doctrine\RegistryInterface;

abstract class AbstractRuleEntityListener
{
    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldProvider;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param PriceRuleFieldsProvider $fieldsProvider
     * @param RegistryInterface $registry
     */
    public function __construct(
        PriceListTriggerHandler $priceListTriggerHandler,
        PriceRuleFieldsProvider $fieldsProvider,
        RegistryInterface $registry
    ) {
        $this->priceListTriggerHandler = $priceListTriggerHandler;
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

        $this->priceListTriggerHandler->addTriggersForPriceLists(Topics::CALCULATE_RULE, $priceLists, $product);
        $this->updatePriceListActuality($priceLists);
    }

    /**
     * @param array $updatedFields
     * @param null|int $relationId
     * @return array|\Oro\Bundle\PricingBundle\Entity\PriceRuleLexeme[]
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
     * @param array|PriceList[] $priceLists
     */
    protected function updatePriceListActuality(array $priceLists)
    {
        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->registry->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);
        $priceListRepository->updatePriceListsActuality($priceLists, false);
    }

    /**
     * @param array $changeSet
     * @param Product $product
     * @param int|null $relationId
     */
    protected function recalculateByEntityFieldsUpdate(array $changeSet, Product $product = null, $relationId = null)
    {
        $fields = $this->getEntityFields();
        $updatedFields = array_intersect($fields, array_keys($changeSet));

        if ($updatedFields) {
            $lexemes = $this->findEntityLexemes($updatedFields, $relationId);
            $this->addTriggersByLexemes($lexemes, $product);
        }
    }

    /**
     * @param Product|null $product
     * @param int|null $relationId
     */
    protected function recalculateByEntity(Product $product = null, $relationId = null)
    {
        $lexemes = $this->findEntityLexemes([], $relationId);
        $this->addTriggersByLexemes($lexemes, $product);
    }

    /**
     * @return array
     */
    protected function getEntityFields()
    {
        return $this->fieldsProvider->getFields($this->getEntityClassName(), false, true);
    }
}
