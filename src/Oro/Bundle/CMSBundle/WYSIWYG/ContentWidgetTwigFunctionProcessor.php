<?php

namespace Oro\Bundle\CMSBundle\WYSIWYG;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Save uses of the content widgets in WYSIWYG fields
 */
class ContentWidgetTwigFunctionProcessor implements WYSIWYGTwigFunctionProcessorInterface
{
    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMapping(): array
    {
        return [
            self::FIELD_CONTENT_TYPE => ['widget'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function processTwigFunctions(WYSIWYGProcessedDTO $processedDTO, array $twigFunctionCalls): bool
    {
        $ownerEntityId = $processedDTO->requireOwnerEntityId();
        if (!\is_numeric($ownerEntityId)) {
            return false;
        }

        $ownerEntityId = (int)$ownerEntityId;
        $ownerEntityClass = $processedDTO->requireOwnerEntityClass();
        $ownerEntityField = $processedDTO->requireOwnerEntityFieldName();

        $actualWidgetCalls = $this->getWidgetNames($twigFunctionCalls);

        $em = $processedDTO->getProcessedEntity()->getEntityManager();
        $currentUsage = $em->getRepository(ContentWidgetUsage::class)
            ->findForEntityField($ownerEntityClass, $ownerEntityId, $ownerEntityField);

        $isFlushNeeded = false;
        // Removing currently not used widgets
        foreach ($currentUsage as $usage) {
            $widgetName = $usage->getContentWidget()->getName();
            if (!isset($actualWidgetCalls[$widgetName])) {
                $em->remove($usage);
                $isFlushNeeded = true;
            } else {
                unset($actualWidgetCalls[$widgetName]);
            }
        }

        // Adding new widget usages
        if ($actualWidgetCalls) {
            foreach ($this->getContentWidgets($processedDTO, \array_keys($actualWidgetCalls)) as $contentWidget) {
                $usage = new ContentWidgetUsage();
                $usage->setContentWidget($contentWidget);
                $usage->setEntityClass($ownerEntityClass);
                $usage->setEntityId($ownerEntityId);
                $usage->setEntityField($ownerEntityField);

                $em->persist($usage);
                $isFlushNeeded = true;
            }
        }

        return $isFlushNeeded;
    }

    protected function getContentWidgets(WYSIWYGProcessedDTO $processedDTO, array $names): array
    {
        return $processedDTO->getProcessedEntity()
            ->getEntityManager()
            ->getRepository(ContentWidget::class)
            ->findAllByNames($names, $this->aclHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRemove(WYSIWYGProcessedDTO $processedDTO): bool
    {
        $ownerEntityId = $processedDTO->requireOwnerEntityId();
        if (!\is_numeric($ownerEntityId)) {
            return false;
        }

        $ownerEntityClass = $processedDTO->requireOwnerEntityClass();

        $em = $processedDTO->getProcessedEntity()->getEntityManager();
        $removeList = $em->getRepository(ContentWidgetUsage::class)
            ->findForEntityField($ownerEntityClass, $ownerEntityId);

        if (!$removeList) {
            return false;
        }

        foreach ($removeList as $usage) {
            $em->remove($usage);
        }

        return true;
    }

    /**
     * @param array $twigFunctionCalls
     * @return array ['widget_name' => true, ...]
     */
    private function getWidgetNames(array $twigFunctionCalls): array
    {
        $actualWidgetCalls = [];
        if (isset($twigFunctionCalls[self::FIELD_CONTENT_TYPE]['widget'])) {
            foreach ($twigFunctionCalls[self::FIELD_CONTENT_TYPE]['widget'] as list($widgetName)) {
                if ($widgetName && \is_string($widgetName)) {
                    $actualWidgetCalls[$widgetName] = true;
                }
            }
        }

        return $actualWidgetCalls;
    }
}
