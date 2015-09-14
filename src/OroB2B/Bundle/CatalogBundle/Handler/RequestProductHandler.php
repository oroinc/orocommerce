<?php

namespace OroB2B\Bundle\CatalogBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\HttpFoundation\Request;

class RequestProductHandler
{
    const CATEGORY_ID_KEY = 'categoryId';
    const INCLUDE_SUBCATEGORIES_KEY = 'includeSubcategories';
    const INCLUDE_SUBCATEGORIES_DEFAULT_VALUE = false;

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

        $value = $this->request->get(self::CATEGORY_ID_KEY);

        if (is_bool($value)) {
            return false;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value > 0) {
            return $value;
        }

        return false;
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
