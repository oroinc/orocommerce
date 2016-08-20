<?php

namespace Oro\Bundle\CatalogBundle\Handler;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestProductHandler
{
    const CATEGORY_ID_KEY = 'categoryId';
    const INCLUDE_SUBCATEGORIES_KEY = 'includeSubcategories';
    const INCLUDE_SUBCATEGORIES_DEFAULT_VALUE = false;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool|integer
     */
    public function getCategoryId()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        $value = $request->get(self::CATEGORY_ID_KEY);

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
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return self::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE;
        }

        $value = filter_var(
            $request->get(self::INCLUDE_SUBCATEGORIES_KEY, self::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if (null === $value) {
            return self::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE;
        }

        return $value;
    }
}
