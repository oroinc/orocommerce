<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Manage redirects for given slugs.
 */
class RedirectGenerator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $from
     * @param Slug $slug
     */
    public function updateRedirects($from, Slug $slug)
    {
        if ($from === $slug->getUrl()) {
            return;
        }

        /** @var RedirectRepository $repository */
        $repository = $this->getRedirectManager()->getRepository(Redirect::class);
        $repository->updateRedirectsBySlug($slug);
        $repository->deleteCyclicRedirects($slug);
    }

    public function generateForSlug(Slug $from, Slug $to)
    {
        if ($from->getUrl() === $to->getUrl()) {
            return;
        }

        $redirect = new Redirect();
        $redirect->setFromPrototype($from->getSlugPrototype());
        $redirect->setFrom($from->getUrl());
        $redirect->setToPrototype($to->getSlugPrototype());
        $redirect->setTo($to->getUrl());
        $redirect->setSlug($to);
        $redirect->setType(Redirect::MOVED_PERMANENTLY);

        $this->getRedirectManager()->persist($redirect);
    }

    /**
     * @return ObjectManager
     */
    private function getRedirectManager()
    {
        return $this->registry->getManagerForClass(Redirect::class);
    }
}
