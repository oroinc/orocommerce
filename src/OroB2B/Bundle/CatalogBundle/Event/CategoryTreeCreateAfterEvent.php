<?php

namespace Oro\Bundle\CatalogBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryTreeCreateAfterEvent extends Event
{
    const NAME = 'orob2b_catalog.category.tree.create_after';

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
