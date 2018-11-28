<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class RedirectGenerator
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
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

    /**
     * @param string $from
     * @param Slug $slug
     */
    public function generate($from, Slug $slug)
    {
        if ($from === $slug->getUrl()) {
            return;
        }

        $fromParts = explode('/', $from);

        $fromSlug = new Slug();
        $fromSlug->setUrl($from);
        $fromSlug->setSlugPrototype(array_pop($fromParts));

        $this->generateForSlug($fromSlug, $slug);
    }

    /**
     * @param Slug $from
     * @param Slug $to
     */
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
