<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductsByAttributesTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Watch changes of product attributes made by the user from UI
 * and trigger update of search index only for active attributes (they should be in status ACTIVE or UPDATE)
 */
class AttributeChangesListener
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var MessageProducerInterface */
    protected $producer;

    /** @var array */
    protected static $activeStates = [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE];

    /** @var array */
    protected $fieldNamesForIndexation = [];

    public function __construct(RequestStack $requestStack, MessageProducerInterface $producer)
    {
        $this->requestStack = $requestStack;
        $this->producer = $producer;
    }

    public function preFlush(PreFlushConfigEvent $event): void
    {
        if (!$this->isPreFlushApplicable($event)) {
            return;
        }

        $attributeConfig = $event->getConfig('attribute');
        $fieldConfigId = $attributeConfig->getId();
        if ($fieldConfigId instanceof FieldConfigId) {
            $this->fieldNamesForIndexation[] = $fieldConfigId->getFieldName();
            $attributeConfig->remove('request_search_indexation');
            $event->getConfigManager()->persist($attributeConfig);
        }
    }

    public function postFlush(PostFlushConfigEvent $event): void
    {
        if (!$this->isPostFlushApplicable()) {
            return;
        }

        $configManager = $event->getConfigManager();
        $modelsForIndexation = [];

        foreach ($event->getModels() as $model) {
            if (!$model instanceof FieldConfigModel) {
                continue;
            }

            $className = $model->getEntity()->getClassName();
            if (!is_a($className, Product::class, true)) {
                continue;
            }

            $fieldName = $model->getFieldName();

            if (!empty($this->fieldNamesForIndexation) &&
                !\in_array($fieldName, $this->fieldNamesForIndexation, true)
            ) {
                continue;
            }

            if ($this->isReindexRequired($configManager, $className, $fieldName)) {
                $modelsForIndexation[] = $model;
            }
        }

        if (!empty($modelsForIndexation)) {
            $this->triggerProductsReindex($modelsForIndexation);
        }

        $this->fieldNamesForIndexation = [];
    }

    /**
     * @param ConfigManager $configManager
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isReindexRequired(ConfigManager $configManager, $className, $fieldName): bool
    {
        $extendConfig = $configManager->getProvider('extend')->getConfig($className, $fieldName);
        $extendChangeSet = $configManager->getConfigChangeSet($extendConfig);

        $isStateActive = $extendConfig->in('state', self::$activeStates);
        $isStateChanged = $this->isStateChanged($extendChangeSet);

        /* Ignore changes of attribute state when state changed from not active to not active
         * or from active to active state
         *
         * For example:
         *  - NEW => DELETE
         *  - ACTIVE => UPDATE
         */
        if (!$isStateChanged && !$isStateActive) {
            return false;
        }

        if ($isStateActive) {
            return $this->isReindexRequiredInActiveState($configManager, $className, $fieldName, $isStateChanged);
        }

        $attributeConfig = $configManager->getProvider('attribute')->getConfig($className, $fieldName);
        $attributeChangeSet = $configManager->getConfigChangeSet($attributeConfig);

        $isSearchable = $attributeConfig->is('searchable');
        $isSearchableChanged = isset($attributeChangeSet['searchable']);

        return $isStateChanged && ($isSearchable ^ $isSearchableChanged);
    }

    /**
     * Change set should have changes of state and active state should be only in one field of state change set
     */
    private function isStateChanged(array $extendChangeSet): bool
    {
        $changeSet = $extendChangeSet['state'] ?? [];

        return $changeSet &&
            in_array($changeSet[0], self::$activeStates, true) ^ in_array($changeSet[1], self::$activeStates, true);
    }

    /**
     * We should trigger update search index in case if state of attribute not changed,
     * but it is in active state and next conditions are met:
     *  - searchable:   no => yes
     *  - searchable:   yes => no
     *  - search_boost: null => value (for searchable)
     *  - filterable:   no => yes
     *  - sortable:     no => yes
     *  - visible:      no => yes
     * or when state of attribute changed and some of required parameters already enabled
     */
    private function isReindexRequiredInActiveState(
        ConfigManager $configManager,
        string $className,
        string $fieldName,
        bool $isStateChanged
    ): bool {
        $attributeConfig = $configManager->getProvider('attribute')->getConfig($className, $fieldName);
        $frontendConfig = $configManager->getProvider('frontend')->getConfig($className, $fieldName);

        $isSearchable = $attributeConfig->is('searchable');
        $isFilterable = $attributeConfig->is('filterable');
        $isSortable = $attributeConfig->is('sortable');
        $isVisible = $frontendConfig->is('is_displayable');

        $isAnyOptionEnabled = array_filter([$isSearchable, $isFilterable, $isSortable, $isVisible]);

        $attributeChangeSet = $configManager->getConfigChangeSet($attributeConfig);
        $frontendChangeSet = $configManager->getConfigChangeSet($frontendConfig);

        $isAnyOptionChangedToEnabled = array_filter(
            [
                isset($attributeChangeSet['searchable']),
                $isSearchable && $this->isSearchBoostEnabled($attributeChangeSet),
                $isFilterable && isset($attributeChangeSet['filterable']),
                $isSortable && isset($attributeChangeSet['sortable']),
                $isVisible && isset($frontendChangeSet['is_displayable'])
            ]
        );

        if (($isStateChanged && $isAnyOptionEnabled) || (!$isStateChanged && $isAnyOptionChangedToEnabled)) {
            return true;
        }

        return false;
    }

    /**
     * @param FieldConfigModel[] $modelsForIndexation
     */
    protected function triggerProductsReindex(array $modelsForIndexation): void
    {
        $attributeIds = array_map(static function (FieldConfigModel $fieldConfigModel) {
            return $fieldConfigModel->getId();
        }, $modelsForIndexation);

        $this->producer->send(ReindexProductsByAttributesTopic::getName(), ['attributeIds' => $attributeIds]);
    }

    protected function isSearchBoostEnabled(array $attributeChangeSet): bool
    {
        $isSearchBoostEnabled = false;

        if (isset($attributeChangeSet['search_boost'])) {
            [$searchBoostOldValue, $searchBoostNewValue] = $attributeChangeSet['search_boost'];

            $isSearchBoostEnabled = !$searchBoostOldValue && $searchBoostNewValue;
        }

        return $isSearchBoostEnabled;
    }

    private function isPreFlushApplicable(PreFlushConfigEvent $event): bool
    {
        if (!$event->isFieldConfig()) {
            return false;
        }

        if (!is_a($event->getClassName(), Product::class, true)) {
            return false;
        }

        $attributeConfig = $event->getConfig('attribute');
        if (!$attributeConfig) {
            return false;
        }

        return (bool) $attributeConfig->get('request_search_indexation');
    }

    private function isPostFlushApplicable(): bool
    {
        if (!empty($this->fieldNamesForIndexation) || $this->requestStack->getMainRequest()) {
            return true;
        }

        return false;
    }
}
