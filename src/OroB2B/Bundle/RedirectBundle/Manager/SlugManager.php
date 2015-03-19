<?php

namespace OroB2B\Bundle\RedirectBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\RedirectBundle\Entity\Slug;

class SlugManager
{
    /**
     *  @var Doctrine
     */
    private $doctrine;

    /**
     * Constructor
     *
     * @param Doctrine $doctrine
     */
    public function __construct(Doctrine $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Get current EntityManager
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->doctrine->getManager();
    }

    /**
     * Set unique url for Slug entity
     *
     * @param string $url
     * @return string
     */
    public function setUniqueUrlForSlug(Slug $slug)
    {
        $existedSlug = $this->findSlugByUrl($slug->getUrl());
        if (null !== $existedSlug && $existedSlug->getId() !== $slug->getId()) {
            $incrementUrl = $this->incrementUrl($slug->getUrl());

            while (null !== $this->findSlugByUrl($incrementUrl)) {
                $incrementUrl = $this->incrementUrl($incrementUrl);
            }

            $slug->setUrl($incrementUrl);
        }
    }

    /**
     * Check is Slug url exists
     *
     * @param Slug $slug
     * @return bool
     */
    public function findSlugByUrl($url)
    {
        return $this
            ->getEntityManager()
            ->getRepository('OroB2BRedirectBundle:Slug')
            ->findOneByUrl($url);
    }

    /**
     * Get incremented url
     *
     * @param string $url
     * @return string
     */
    public function incrementUrl($url)
    {
        $version = 0;

        if (preg_match('/(.*)-(\d*)$/', $url, $matches)) {
            $url     = $matches[1];
            $version = $matches[2];
        }

        $version += 1;

        return sprintf('%s-%d', $url, $version);
    }
}
