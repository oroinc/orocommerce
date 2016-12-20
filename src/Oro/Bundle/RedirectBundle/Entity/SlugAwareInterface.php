<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface SlugAwareInterface
{
    /**
     * @return Collection|Slug[]
     */
    public function getSlugs();

    /**
     * @param Slug $slug
     * @return $this
     */
    public function addSlug(Slug $slug);

    /**
     * @param Slug $slug
     * @return $this
     */
    public function removeSlug(Slug $slug);

    /**
     * @return $this
     */
    public function resetSlugs();

    /**
     * @param Slug $slug
     * @return bool
     */
    public function hasSlug(Slug $slug);
}
