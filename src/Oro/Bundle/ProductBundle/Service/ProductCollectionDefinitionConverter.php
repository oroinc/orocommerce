<?php

namespace Oro\Bundle\ProductBundle\Service;

/**
 * Allow converting Segment definition into definition without excluded and included filters,
 * and incorporate excluded&included filters into definition.
 */
class ProductCollectionDefinitionConverter
{
    /**
     * Put excluded or\and included filters into definition.
     *
     * @param $definition
     * @param $excluded
     * @param $included
     *
     * @return array
     */
    public function putConditionsInDefinition($definition, $excluded = null, $included = null): array
    {
        // Stub, will be implemented in BB-9438
        return $definition;
    }

    /**
     * Receive definition and return definition without excluded&included,
     * the later will be returned as separate elements. Example or returned array:
     * [
     *      'definition' => definition array, without excluded&included
     *      'excluded'   => excluded filter value, if were found in definition
     *      'included'   => included filter value, if were found in definition
     * ]
     *
     * @param $definition
     *
     * @return array
     */
    public function getDefinitionParts(array $definition): array
    {
        // Stub, will be implemented in BB-9438
        return [
            'definition' => $definition
        ];
    }
}
