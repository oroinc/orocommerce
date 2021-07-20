<?php

namespace Oro\Bundle\CatalogBundle\Handler;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Request product handler class
 */
class RequestProductHandler
{
    const CATEGORY_ID_KEY = 'categoryId';
    const INCLUDE_SUBCATEGORIES_KEY = 'includeSubcategories';
    const INCLUDE_SUBCATEGORIES_DEFAULT_VALUE = false;
    const INCLUDE_NOT_CATEGORIZED_PRODUCTS_DEFAULT_VALUE = false;
    const INCLUDE_NOT_CATEGORIZED_PRODUCTS_KEY = 'includeNotCategorizedProducts';
    const OVERRIDE_VARIANT_CONFIGURATION_KEY = 'overrideVariantConfiguration';

    /** @var RequestStack */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return bool|integer
     */
    public function getCategoryId()
    {
        return $this->resolveValue(self::CATEGORY_ID_KEY);
    }

    public function getCategoryContentVariantId(): int
    {
        return $this->resolveValue(CategoryPageContentVariantType::CATEGORY_CONTENT_VARIANT_ID_KEY);
    }

    private function resolveValue(string $name): int
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return 0;
        }

        $value = $request->get($name);

        if (is_bool($value)) {
            return 0;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value > 0) {
            return $value;
        }

        return 0;
    }

    /**
     * @return bool
     */
    public function getOverrideVariantConfiguration()
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return false;
        }

        return filter_var(
            $request->get(CategoryPageContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY),
            FILTER_VALIDATE_BOOLEAN
        );
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

        return (bool) $this->getChoice(self::INCLUDE_SUBCATEGORIES_KEY, $defaultValue);
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
