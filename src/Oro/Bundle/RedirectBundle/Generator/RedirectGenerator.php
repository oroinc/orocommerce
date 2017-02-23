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

        $redirect = new Redirect();
        $redirect->setFrom($from);
        $redirect->setTo($slug->getUrl());
        $redirect->setSlug($slug);
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
