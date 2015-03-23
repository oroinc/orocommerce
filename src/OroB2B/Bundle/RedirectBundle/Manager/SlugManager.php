<?php

namespace OroB2B\Bundle\RedirectBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;

class SlugManager
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * Constructor
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * Set unique url for Slug entity
     *
     * @param Slug $slug
     */
    public function makeUrlUnique(Slug $slug)
    {
        $existingSlug = $this->findSlugByUrl($slug->getUrl());
        if (null !== $existingSlug && $existingSlug->getId() !== $slug->getId()) {
            $incrementedUrl = $this->incrementUrl($slug->getUrl());

            while (null !== $this->findSlugByUrl($incrementedUrl)) {
                $incrementedUrl = $this->incrementUrl($incrementedUrl);
            }

            $slug->setUrl($incrementedUrl);
        }
    }

    /**
     * Get current EntityManager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass('OroB2BRedirectBundle:Slug');
    }

    /**
     * Check is Slug url exists
     *
     * @param string $url
     * @return Slug|null
     */
    protected function findSlugByUrl($url)
    {
        return $this
            ->getEntityManager()
            ->getRepository('OroB2BRedirectBundle:Slug')
            ->findOneBy(['url' => $url]);
    }

    /**
     * Get incremented url
     *
     * @param string $url
     * @return string
     */
    protected function incrementUrl($url)
    {
        $version = 0;

        if (preg_match('/^(.*)-(\d+)$/', $url, $matches)) {
            $url     = $matches[1];
            $version = $matches[2];
        }

        $version++;

        return sprintf('%s-%d', $url, $version);
    }
}
