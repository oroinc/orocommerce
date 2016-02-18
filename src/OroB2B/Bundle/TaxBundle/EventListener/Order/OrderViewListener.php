<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Symfony\Component\Translation\TranslatorInterface;

class OrderViewListener
{
    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var TaxManager */
    protected $taxManager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $entityClass;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     * @param string $entityClass
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator,
        $entityClass
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
        $this->entityClass = (string)$entityClass;
    }

    /** {@inheritdoc} */
    public function onView(BeforeListRenderEvent $event)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $entityId = filter_var($request->get('id'), FILTER_VALIDATE_INT);
        if (false === $entityId) {
            return;
        }

        $entity = $this->doctrineHelper->getEntityReference($this->entityClass, $entityId);

        $result = $this->taxManager->loadTax($entity);
        if (!$result->count()) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroB2BTaxBundle::view.html.twig',
            ['result' => $result]
        );

        $scrollData = $event->getScrollData();
        $blockId = $scrollData->addBlock($this->translator->trans('orob2b.tax.result.label'), -15);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
