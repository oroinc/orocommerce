<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Twig\UiExtension;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Render WYSIWYG fields of related entities in separated sections.
 */
class WYSIWYGBlockListener
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigProvider */
    private $entityConfigProvider;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityConfigProvider = $configProvider;
        $this->translator = $translator;
    }

    public function onBeforeFormRender(BeforeFormRenderEvent $event): void
    {
        if (!$event->getEntity()) {
            return;
        }

        $className = $this->doctrineHelper->getEntityClass($event->getEntity());

        $fieldConfigs = $this->entityConfigProvider->getIds($className);
        if (!$fieldConfigs) {
            return;
        }

        //Turn array data to DTO to make possible to manipulate blocks
        $scrollData = new ScrollData($event->getFormData());
        //Wysiwyg section renders before Additional
        $wysiwygSectionPriority = UiExtension::ADDITIONAL_SECTION_PRIORITY - 1;

        foreach ($fieldConfigs as $fieldConfig) {
            $fieldName = $fieldConfig->getFieldName();
            if ($fieldConfig->getFieldType() === WYSIWYGType::TYPE &&
                $scrollData->hasNamedField($fieldName)
            ) {
                $config = $this->entityConfigProvider->getConfig($className, $fieldName);
                $newBlockKey = $fieldName . '_block_section';

                $scrollData->addNamedBlock(
                    $newBlockKey,
                    $this->translator->trans((string) $config->get('label')),
                    $wysiwygSectionPriority
                );

                $scrollData->moveFieldToBlock($fieldName, $newBlockKey);
            }
        }

        //Removes Additional section if no subblocks left
        if ($scrollData->hasBlock(UiExtension::ADDITIONAL_SECTION_KEY)
            && $scrollData->isEmptyBlock(UiExtension::ADDITIONAL_SECTION_KEY)) {
            $scrollData->removeNamedBlock(UiExtension::ADDITIONAL_SECTION_KEY);
        }

        $event->setFormData($scrollData->getData());
    }
}
