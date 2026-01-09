<?php

namespace Oro\Bundle\CatalogBundle\Event;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after a category tree is created.
 *
 * Provides access to the created categories and allows listeners to perform additional
 * operations or modifications after the category tree creation process is complete.
 */
class CategoryTreeCreateAfterEvent extends Event
{
    public const NAME = 'oro_catalog.category.tree.create_after';

    /**
     * @var UserInterface|null
     */
    protected $user;

    /**
     * @var Category[]
     */
    protected $categories;

    /**
     * @param array $categories
     */
    public function __construct($categories)
    {
        $this->setCategories($categories);
    }

    /**
     * @return null|UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param null|UserInterface $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param Category[] $categories
     * @return $this
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;

        return $this;
    }
}
