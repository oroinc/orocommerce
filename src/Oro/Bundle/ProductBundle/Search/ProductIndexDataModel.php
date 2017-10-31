<?php

namespace Oro\Bundle\ProductBundle\Search;

class ProductIndexDataModel
{
    /** @var string */
    protected $fieldName;

    /** @var mixed */
    protected $value;

    /** @var array */
    protected $placeholders = [];

    /** @var bool */
    protected $localized;

    /** @var bool */
    protected $searchable;

    /**
     * @param string $fieldName
     * @param mixed $value
     * @param array $placeholders
     * @param bool $localized
     * @param bool $searchable
     */
    public function __construct($fieldName, $value, array $placeholders, $localized, $searchable)
    {
        $this->fieldName = (string)$fieldName;
        $this->value = $value;
        $this->placeholders = $placeholders;
        $this->localized = (bool)$localized;
        $this->searchable = (bool)$searchable;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * @return bool
     */
    public function isLocalized()
    {
        return $this->localized;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
    }
}
