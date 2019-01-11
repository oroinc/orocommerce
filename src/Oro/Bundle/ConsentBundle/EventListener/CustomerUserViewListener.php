<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Oro\Bundle\ConsentBundle\Provider\CustomerUserConsentProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Adds consents section to the Customer User view page in admin panel
 */
class CustomerUserViewListener
{
    use FeatureCheckerHolderTrait;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var CustomerUserConsentProvider */
    protected $customerUserConsentProvider;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     * @param CustomerUserConsentProvider $customerUserConsentProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        RequestStack $requestStack,
        CustomerUserConsentProvider $customerUserConsentProvider
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
        $this->customerUserConsentProvider = $customerUserConsentProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function onCustomerUserView(BeforeListRenderEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntityFromRequestId(CustomerUser::class);

        if ($customerUser) {
            if (!$this->customerUserConsentProvider->hasEnabledConsentsByCustomerUser($customerUser)) {
                return;
            }
            $consentsWithAcceptances = $this->customerUserConsentProvider->getCustomerUserConsentsWithAcceptances(
                $customerUser
            );

            $template = $event->getEnvironment()->render(
                $this->getCustomerUserViewTemplate(),
                ['consents' => $consentsWithAcceptances]
            );
            $this->addConsentsBlock(
                $event->getScrollData(),
                $template,
                $this->translator->trans($this->getCustomerUserLabel())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserViewTemplate()
    {
        return 'OroConsentBundle:CustomerUser:consent_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserLabel()
    {
        return 'oro.consent.entity_plural_label';
    }

    /**
     * @param $className
     * @return null|object
     */
    protected function getEntityFromRequestId($className)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $entityId = (int)$request->get('id');
        if (!$entityId) {
            return null;
        }

        $entity = $this->doctrineHelper->getEntityRepository($className)->find($entityId);
        if (!$entity) {
            return null;
        }

        return $entity;
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     * @param string $blockLabel
     */
    protected function addConsentsBlock(ScrollData $scrollData, $html, $blockLabel)
    {
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
