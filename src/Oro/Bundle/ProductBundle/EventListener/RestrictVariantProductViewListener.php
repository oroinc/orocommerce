<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Disallow direct access to variant product view pages is disallowed by the feature.
 */
class RestrictVariantProductViewListener extends AbstractRestrictProductViewListener implements
    FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    protected function restrictProductView(Product $product, ControllerEvent $event)
    {
        $request = $event->getRequest();
        $isLayoutUpdateRequest = $request->isXmlHttpRequest() && $request->get('layout_block_ids');
        if (!$isLayoutUpdateRequest && $this->isFeaturesEnabled() && $product->getParentVariantLinks()->count()) {
            throw new AccessDeniedHttpException(sprintf(
                'Product variant view is restricted by system config. Product id: %d',
                $product->getId()
            ));
        }
    }
}
