<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * View listener for removing brand field from sub blocks
 * of scroll event data if a user hasn't permission to view the brand.
 */
class ProductBrandViewListener
{
    private const BRAND_KEY = 'brand';

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function onViewList(BeforeListRenderEvent $event): void
    {
        if (!$event->getEntity() instanceof Product) {
            return;
        }

        if (!$this->authorizationChecker->isGranted('oro_product_brand_view') ||
            !$this->authorizationChecker->isGranted('VIEW', $event->getEntity()->getBrand())
        ) {
            $event->getScrollData()->removeField(self::BRAND_KEY);
        }
    }
}
