<?php

namespace Oro\Bundle\VisibilityBundle\Api;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;

/**
 * Provides a set methods to work with composite identifiers used in API for visibility entities.
 */
class VisibilityIdHelper
{
    private const DELIMITER = '-';

    /**
     * Extracts entity identifier with the given property path
     * from the given composite identifier of a visibility entity.
     *
     * @param array  $visibilityId [property path => value, ...]
     * @param string $propertyPath
     *
     * @return int
     *
     * @throws \InvalidArgumentException if an entity identifier is not valid
     */
    public function getId(array $visibilityId, string $propertyPath): int
    {
        if (!isset($visibilityId[$propertyPath])) {
            throw new \InvalidArgumentException(sprintf(
                'The "%s" item does not exist in a composite visibility identifier.',
                $propertyPath
            ));
        }

        return $visibilityId[$propertyPath];
    }

    /**
     * Builds a string that represents a composite identifier of a visibility entity.
     *
     * @param array                       $visibilityId [property path => value, ...]
     * @param EntityDefinitionFieldConfig $idFieldConfig
     *
     * @return string
     *
     * @throws \InvalidArgumentException if the given visibility identifier is not valid
     */
    public function encodeVisibilityId(
        array $visibilityId,
        EntityDefinitionFieldConfig $idFieldConfig
    ): string {
        $items = [];
        $propertyPaths = $idFieldConfig->getDependsOn() ?? [];
        foreach ($propertyPaths as $propertyPath) {
            if (!\array_key_exists($propertyPath, $visibilityId)) {
                throw new \InvalidArgumentException(sprintf(
                    'A value for "%s" must exist in a visibility identifier.',
                    $propertyPath
                ));
            }
            $value = $visibilityId[$propertyPath];
            if (null === $value) {
                throw new \InvalidArgumentException(sprintf(
                    'A value for "%s" in a visibility identifier must be not null.',
                    $propertyPath
                ));
            }
            if (!\is_int($value) || $value < 0) {
                throw new \InvalidArgumentException(sprintf(
                    'A value for "%s" in a visibility identifier must be an integer greater than or equals to zero.',
                    $propertyPath
                ));
            }
            $items[] = (string)$value;
        }

        return implode(self::DELIMITER, $items);
    }

    /**
     * Extracts identifiers of entities from a string that represents a composite identifier of a visibility entity.
     *
     * @param string                      $encodedVisibilityId
     * @param EntityDefinitionFieldConfig $idFieldConfig
     *
     * @return array|null [property path => value, ...]
     *                    NULL is returned when the given ID cannot be decoded
     */
    public function decodeVisibilityId(
        string $encodedVisibilityId,
        EntityDefinitionFieldConfig $idFieldConfig
    ): ?array {
        $propertyPaths = $idFieldConfig->getDependsOn() ?? [];
        $count = \count($propertyPaths);
        $values = explode(self::DELIMITER, $encodedVisibilityId);
        if (\count($values) !== $count) {
            return null;
        }

        $visibilityId = [];
        $i = 0;
        foreach ($propertyPaths as $propertyPath) {
            $value = $values[$i];
            if (!preg_match('/^\d+$/', $value)) {
                return null;
            }
            $visibilityId[$propertyPath] = (int)$value;
            $i++;
        }

        return $visibilityId;
    }
}
