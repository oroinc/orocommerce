<?php

namespace Oro\Bundle\WebCatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The provider of the web content variant for the current storefront request.
 */
class RequestWebContentVariantProvider
{
    private const REQUEST_CONTENT_VARIANT_ATTRIBUTE = '_content_variant';
    private const REQUEST_USED_SLUG_ATTRIBUTE       = '_used_slug';

    /** @var RequestStack */
    private $requestStack;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine
    ) {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
    }

    public function getContentVariant(): ?ContentVariant
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || $this->requestStack->getMainRequest() !== $request) {
            return null;
        }

        if ($request->attributes->has(self::REQUEST_CONTENT_VARIANT_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_CONTENT_VARIANT_ATTRIBUTE);
        }

        $contentVariant = null;
        $slug = $request->attributes->get(self::REQUEST_USED_SLUG_ATTRIBUTE);
        if ($slug) {
            $contentVariant = $this->getContentVariantRepository()->findVariantBySlug($slug);
        }
        $request->attributes->set(self::REQUEST_CONTENT_VARIANT_ATTRIBUTE, $contentVariant);

        return $contentVariant;
    }

    private function getContentVariantRepository(): ContentVariantRepository
    {
        return $this->doctrine->getRepository(ContentVariant::class);
    }
}
