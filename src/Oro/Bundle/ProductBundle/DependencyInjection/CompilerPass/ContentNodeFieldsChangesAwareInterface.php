<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

/**
 * Defines the contract for services that track changes to content node fields.
 *
 * Implementations of this interface can register field names that should be monitored for changes in content nodes,
 * enabling custom logic to respond to field modifications.
 */
interface ContentNodeFieldsChangesAwareInterface
{
    /**
     * @param string $fieldName
     *
     * @return ContentNodeFieldsChangesAwareInterface
     */
    public function addField($fieldName);

    /**
     * @return array
     */
    public function getFields();
}
