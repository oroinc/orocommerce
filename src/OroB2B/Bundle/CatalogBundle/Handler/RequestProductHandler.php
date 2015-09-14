<?php

namespace OroB2B\Bundle\CatalogBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class RequestProductHandler
{
    const CATEGORY_ID_KEY = 'categoryId';
    const INCLUDE_SUBCATEGORIES_KEY = 'includeSubcategories';
    const INCLUDE_SUBCATEGORIES_DEFAULT_VALUE = true;

    /** @var  Request|null */
    protected $request;

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @return bool|integer
     */
    public function getCategoryId()
    {
        if (!$this->request) {
            return false;
        }

        return filter_var($this->request->get(self::CATEGORY_ID_KEY), FILTER_VALIDATE_INT);
    }

    /**
     * @return bool
     */
    public function getIncludeSubcategoriesChoice()
    {
        if (!$this->request) {
            return self::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE;
        }

        $value = filter_var(
            $this->request->get(self::INCLUDE_SUBCATEGORIES_KEY, self::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        if (null === $value) {
            return self::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE;
        }

        return $value;
    }
}
