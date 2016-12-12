<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListener
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$event->isMasterRequest() || !$request->attributes->has('_used_slug')) {
            return;
        }

        $slug = $request->attributes->get('_used_slug');
        $contentVariant = $this->getRepository()->findVariantBySlug($slug);
        if ($contentVariant) {
            $request->attributes->set('_content_variant', $contentVariant);
        }
    }

    /**
     * @return ContentVariantRepository
     */
    private function getRepository()
    {
        return $this->registry
            ->getManagerForClass(ContentVariant::class)
            ->getRepository(ContentVariant::class);
    }
}
