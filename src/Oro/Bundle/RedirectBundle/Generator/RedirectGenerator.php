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
     * @return Redirect|null
     */
    public function generate($from, Slug $toSlug)
    {
        return $this->createRedirect($from, $toSlug);
    }

    /**
     * @param string $from
     * @param Slug $toSlug
     * @param int $redirectType
     * @return Redirect
     */
    protected function createRedirect($from, Slug $toSlug, $redirectType = Redirect::MOVED_PERMANENTLY)
    {
        $redirect = $this->getExistingRedirectForSlug($toSlug);
        if ($redirect) {
            $redirect->setTo($toSlug->getUrl());
        } else {
            $redirect = $this->createNewRedirect($from, $toSlug, $redirectType);
        }

        return $redirect;
    }

    /**
     * @param Slug $slug
     * @return null|Redirect
     */
    private function getExistingRedirectForSlug(Slug $slug)
    {
        return $this->registry->getManagerForClass(Redirect::class)
            ->getRepository(Redirect::class)
            ->findOneBy(['slug' => $slug]);
    }

    /**
     * @param string $from
     * @param Slug $toSlug
     * @param int $redirectType
     * @return Redirect
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

        return $redirect;
    }
}
