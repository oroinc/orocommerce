<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;

/**
 * Manage redirects for given slugs.
 */
class RedirectGenerator
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function updateRedirects(string $from, Slug $slug): void
    {
        if ($from === $slug->getUrl()) {
            return;
        }

        /** @var RedirectRepository $repository */
        $repository = $this->doctrine->getRepository(Redirect::class);
        $repository->updateRedirectsBySlug($slug);
        $repository->deleteCyclicRedirects($slug);
    }

    public function generateForSlug(Slug $from, Slug $to): void
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

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Redirect::class);
        $em->persist($redirect);
    }
}
