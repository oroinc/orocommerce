<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType as DBALWYSIWYGType;
use Oro\Bundle\CMSBundle\Helper\WYSIWYGSchemaHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Event\PreFlushConfigEvent;

/**
 * Add additional options to schema for generate methods for WYSIWYG style field
 */
class WYSIWYGSerializedConfigListener
{
    /**
     * @var WYSIWYGSchemaHelper
     */
    private $wysiwygSchemaHelper;

    /**
     * @param WYSIWYGSchemaHelper $wysiwygSchemaHelper
     */
    public function __construct(WYSIWYGSchemaHelper $wysiwygSchemaHelper)
    {
        $this->wysiwygSchemaHelper = $wysiwygSchemaHelper;
    }

    /**
     * @param PreFlushConfigEvent $event
     */
    public function preFlush(PreFlushConfigEvent $event)
    {
        $fieldConfig = $event->getConfig('extend');
        $configManager = $event->getConfigManager();
        if (null !== $fieldConfig && $event->isFieldConfig()) {
            $entityConfig = $configManager->getEntityConfig('extend', $fieldConfig->getId()->getClassName());
            if (!$entityConfig->is('is_extend')) {
                return;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            if (DBALWYSIWYGType::TYPE === $fieldConfigId->getFieldType() && $fieldConfig->is('is_serialized')) {
                $this->wysiwygSchemaHelper->createAdditionalFields($entityConfig, $fieldConfig);
            }
        }
    }
}
