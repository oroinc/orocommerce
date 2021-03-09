<?php
namespace Oro\Component\Expression;

/**
 * Provides information about entity fields.
 */
interface FieldsProviderInterface
{
    /**
     * @param string $className
     * @param bool $numericOnly
     * @param bool $withRelations
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
}
