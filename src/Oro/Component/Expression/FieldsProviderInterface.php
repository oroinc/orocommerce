<?php
namespace Oro\Component\Expression;

/**
 * Defines the interface for FieldsProvider functionality
 */
interface FieldsProviderInterface
{
    /**
     * @param string $className
     * @param bool|false $numericOnly
     * @param bool|false $withRelations
     * @return array
     * @throws \Exception
     */
    public function getFields($className, $numericOnly = false, $withRelations = false);

    /**
     * @param string $className
     * @param bool $numericOnly
     * @param bool $withRelations
     * @return array
     */
    public function getDetailedFieldsInformation($className, $numericOnly = false, $withRelations = false);

    /**
     * @param string $className
     * @param null|string $fieldName
     * @return string
     */
    public function getRealClassName($className, $fieldName = null);

    /**
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    public function isRelation($className, $fieldName);

    /**
     * @param string $className
     * @return null|string
     */
    public function getIdentityFieldName($className);

    /**
     * @return array
     */
    public function getSupportedNumericTypes();

    /**
     * @return array
     */
    public function getSupportedRelationTypes();

    /**
     * @return array
     */
    public function getFieldsWhiteList();

    /**
     * @return array
     */
    public function getFieldsBlackList();
}
