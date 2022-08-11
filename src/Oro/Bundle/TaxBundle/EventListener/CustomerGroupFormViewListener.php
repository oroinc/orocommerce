<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds tax code to the customer group view and edit pages.
 */
class CustomerGroupFormViewListener
{
    private RequestStack $requestStack;
    private DoctrineHelper $doctrineHelper;
    private FeatureChecker $featureChecker;

    public function __construct(
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        FeatureChecker $featureChecker
    ) {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
        $this->featureChecker = $featureChecker;
    }

    public function onView(BeforeListRenderEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        if (!$this->featureChecker->isResourceEnabled(CustomerTaxCode::class, 'entities')) {
            return;
        }

        /** @var CustomerGroup|null $customerGroup */
        $customerGroup = $this->doctrineHelper->getEntityReference(CustomerGroup::class, (int)$request->get('id'));
        if (null === $customerGroup) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroTax/CustomerGroup/tax_code_view.html.twig',
            ['entity' => $customerGroup->getTaxCode()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }

    public function onEdit(BeforeListRenderEvent $event): void
    {
        if (!$this->featureChecker->isResourceEnabled(CustomerTaxCode::class, 'entities')) {
            return;
        }

        $template = $event->getEnvironment()->render(
            '@OroTax/CustomerGroup/tax_code_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $event->getScrollData()->addSubBlockData(0, 0, $template);
    }
}
