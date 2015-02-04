<?php

namespace OroB2B\Bundle\AttributeBundle\AttributeType;

use Symfony\Component\Validator\Constraint;

use OroB2B\Bundle\AttributeBundle\Validator\Constraints\AttributeConstraintInterface;

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
     * Key 'type' is required, key 'options' is optional
     * e.g. [
     *      'type'  => 'integer',
     *      'options' => [
     *          'data' => 0,
     *          'precision' => 0
     *      ]
     * ]
     *
     * @return array
     */
    public function getFormParameters();

    /**
     * Gets required validation constraints
     *
     * @return Constraint[]
     */
    public function getRequiredConstraints();

    /**
     * Gets optional validation constraints
     *
     * @return Constraint[]|AttributeConstraintInterface[]
     */
    public function getOptionalConstraints();

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
