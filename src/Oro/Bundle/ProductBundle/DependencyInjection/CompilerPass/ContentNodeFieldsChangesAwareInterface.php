<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

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
