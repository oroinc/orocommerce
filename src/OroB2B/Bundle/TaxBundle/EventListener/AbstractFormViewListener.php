<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\View\ScrollData;

abstract class AbstractFormViewListener
{
    /**  @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var string */
    protected $taxCodeClass;

    /** @var string */
    protected $entityClass;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     * @param string $taxCodeClass
     * @param string $entityClass
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack,
        $taxCodeClass,
        $entityClass
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
        $this->taxCodeClass = $taxCodeClass;
        $this->entityClass = $entityClass;
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     * @param string $title
     */
    protected function addTaxCodeBlock(ScrollData $scrollData, $html, $title)
    {
        $blockLabel = $this->translator->trans($title);
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }

    /**
     * @return null|object
     */
    protected function getEntityFromRequest()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $accountId = filter_var($request->get('id'), FILTER_VALIDATE_INT);
        if (false === $accountId) {
            return null;
        }

        return $this->doctrineHelper->getEntityReference($this->entityClass, $accountId);
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    abstract public function onEdit(BeforeListRenderEvent $event);

    /**
     * @param BeforeListRenderEvent $event
     */
    abstract public function onView(BeforeListRenderEvent $event);
}
