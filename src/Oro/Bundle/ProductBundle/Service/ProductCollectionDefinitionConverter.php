<?php

namespace Oro\Bundle\ProductBundle\Service;

/**
 * Allow converting Segment definition into definition without excluded and included filters,
 * and incorporate excluded&included filters into definition.
 */
class ProductCollectionDefinitionConverter
{
    const INCLUDED_FILTER_KEY = 'included';
    const EXCLUDED_FILTER_KEY = 'excluded';
    const DEFINITION_KEY = 'definition';

    /**
     * Put excluded or\and included filters into definition.
     *
     * @param string $definition
     * @param string $excluded
     * @param string $included
     *
     * @return string
     */
    public function putConditionsInDefinition($definition, $excluded = null, $included = null)
    {
        // Stub, will be implemented in BB-9438
        return $definition;
    }

    /**
     * Receive definition and return definition without excluded&included,
     * the later will be returned as separate elements. Example or returned array:
     * [
     *      'definition' => definition string as json encoded array, without excluded&included
     *      'excluded'   => excluded filter value string, if were found in definition
     *      'included'   => included filter value string, if were found in definition
     * ]
     *
     * @param array $definition
     *
     * @return array
     */
    public function getDefinitionParts(array $definition)
    {
        // Stub, will be implemented in BB-9438
        return [
            self::DEFINITION_KEY => $definition,
            self::INCLUDED_FILTER_KEY => '1,2,3',
            self::EXCLUDED_FILTER_KEY => '4'
        ];
    }
}
