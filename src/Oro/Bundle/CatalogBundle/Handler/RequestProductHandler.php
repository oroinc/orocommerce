<?php

namespace Oro\Bundle\CatalogBundle\Handler;

use Symfony\Component\HttpFoundation\RequestStack;

class RequestProductHandler
{
    const CATEGORY_ID_KEY = 'categoryId';
    const INCLUDE_SUBCATEGORIES_KEY = 'includeSubcategories';
    const INCLUDE_SUBCATEGORIES_DEFAULT_VALUE = false;
    const INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE = false;
    const INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY = 'includeNotCategorizedProducts';

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
     * @param bool|null $defaultValue
     * @return bool
     */
    public function getIncludeSubcategoriesChoice($defaultValue = null)
    {
        if ($defaultValue === null) {
            $defaultValue = self::INCLUDE_SUBCATEGORIES_DEFAULT_VALUE;
        }

        return $this->getChoice(self::INCLUDE_SUBCATEGORIES_KEY, $defaultValue);
    }

    /**
     * @return bool
     */
    public function getIncludeNotCategorizedProductsChoice()
    {
        return $this->getChoice(
            self::INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY,
            self::INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE
        );
    }

    /**
     * @param string $key
     * @param bool $defaultValue
     * @return bool
     */
    protected function getChoice($key, $defaultValue)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $defaultValue;
        }

        $value = filter_var(
            $request->get($key, $defaultValue),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if (null === $value) {
            return $defaultValue;
        }

        return $value;
    }
}
