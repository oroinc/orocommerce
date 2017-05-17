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
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $slug = $request->attributes->get('_used_slug');
        if (!$slug && $request->attributes->has('_context_url_attributes')) {
            $contextUrlAttributes = $request->attributes->get('_context_url_attributes');
            $slug = isset($contextUrlAttributes[0]['_used_slug']) ?
                $contextUrlAttributes[0]['_used_slug'] : null;
        }
        if (!$slug) {
            return;
        }

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
