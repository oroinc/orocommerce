<?php

namespace Oro\Bundle\RedirectBundle\Generator;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\RedirectBundle\Entity\Redirect;
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
     * @param Slug $toSlug
     */
    public function generate($from, Slug $toSlug)
    {
        $redirects = $this->getExistingRedirectsForSlug($toSlug);
        if ($redirects) {
            foreach ($redirects as $redirect) {
                $redirect->setTo($toSlug->getUrl());
            }
        }

        $this->createNewRedirect($from, $toSlug, Redirect::MOVED_PERMANENTLY);
    }

    /**
     * @param Slug $slug
     * @return Redirect[]
     */
    private function getExistingRedirectsForSlug(Slug $slug)
    {
        return $this->registry->getManagerForClass(Redirect::class)
            ->getRepository(Redirect::class)
            ->findBy(['slug' => $slug]);
    }

    /**
     * @param string $from
     * @param Slug $toSlug
     * @param int $redirectType
     */
    private function createNewRedirect($from, Slug $toSlug, $redirectType = Redirect::MOVED_PERMANENTLY)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(Redirect::class);

        $redirect = new Redirect();
        $redirect->setFrom($from)
            ->setTo($toSlug->getUrl())
            ->setSlug($toSlug)
            ->setType($redirectType);

        $em->persist($redirect);
        $em->flush($redirect);
    }
}
