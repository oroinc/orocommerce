<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

/**
 * Provides an interface of an attribute type
 */
interface AttributeTypeInterface
{
    /**
     * Gets attribute type name
     *
     * @return string
     */
    public function getName();

    /**
     * Gets attribute type data field
     *
     * @return string
     */
    public function getDataTypeField();

    /**
     * Gets form parameters
     * e.g. [
     *      'type'  => 'integer',
     *      'options' => [
     *          'data' => 0,
     *          'precision' => 0
     *      ]
     * ]
     *
     * @param array $options
     * @return array
     */
    public function getFormParameters(array $options = null);

    /**
     * Checks is this attribute type may contain HTML
     *
     * @return bool
     */
    public function isContainHtml();

    /**
     * Checks is this attribute type can be used for search
     *
     * @return bool
     */
    public function isUsedForSearch();

    /**
     * Checks is this attribute type can be used in filters
     *
     * @return bool
     */
    public function isUsedInFilters();
}
