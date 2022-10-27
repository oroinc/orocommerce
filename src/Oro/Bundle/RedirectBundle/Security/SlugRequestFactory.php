<?php

namespace Oro\Bundle\RedirectBundle\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * The main implementation of a slug request factory.
 */
class SlugRequestFactory implements SlugRequestFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createSlugRequest(Request $request): Request
    {
        $slugRequest = Request::create(
            (string) $request->attributes->get('_resolved_slug_url', ''),
            $request->getMethod(),
            $request->query->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
        if ($request->hasSession()) {
            $slugRequest->setSession($request->getSession());
        }
        $slugRequest->setLocale($request->getLocale());
        $slugRequest->setDefaultLocale($request->getDefaultLocale());

        return $slugRequest;
    }

    /**
     * {@inheritDoc}
     */
    public function updateMainRequest(Request $request, Request $slugRequest): void
    {
    }
}
