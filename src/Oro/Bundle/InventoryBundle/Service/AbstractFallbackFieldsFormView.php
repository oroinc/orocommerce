<?php

namespace Oro\Bundle\InventoryBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

abstract class AbstractFallbackFieldsFormView
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $templateName
     * @param string $entity
     */
    public function onEntityView(BeforeListRenderEvent $event, $templateName, $entity)
    {
        $template = $event->getEnvironment()->render(
            $templateName,
            ['entity' => $entity]
        );

        $event->getScrollData()->addSubBlockData(0, 0, $template);

    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $templateName
     * @param null|string $sectionTitle
     */
    public function onEntityEdit(BeforeListRenderEvent $event, $templateName, $sectionTitle = null)
    {
        $template = $event->getEnvironment()->render(
            $templateName,
            ['form' => $event->getFormView()]
        );

        $scrollData = $event->getScrollData();
        if ($sectionTitle === null) {
            $scrollData->addSubBlockData(0, 0, $template);

            return;
        }

        $data = $scrollData->getData();
        $expectedLabel = $this->translator->trans('oro.catalog.sections.default_options');
        foreach ($data['dataBlocks'] as $blockId => $blockData) {
            if ($blockData['title'] == $expectedLabel) {
                $scrollData->addSubBlockData($blockId, 0, $template);

                return;
            }
        }
    }

    /**
     * @return null|object
     */
    protected function getEntityFromRequest($entityPath)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $entityId = (int)$request->get('id');
        if (!$entityId) {
            return null;
        }

        return $this->doctrineHelper->getEntityReference($entityPath, $entityId);
    }
}
