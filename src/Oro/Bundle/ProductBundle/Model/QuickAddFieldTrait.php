<?php

namespace Oro\Bundle\ProductBundle\Model;

trait QuickAddFieldTrait
{
    /**
     * @var QuickAddField[]
     */
    protected $additionalFields = [];

    /**
     * @return QuickAddField[]
     */
    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }

    /**
     * QuickAddField[] $additionalFields
     *
     * @return $this
     */
    public function setAdditionalFields($additionalFields)
    {
        $this->additionalFields = $additionalFields;

        return $this;
    }

    /**
     * @param string $name
     * @return null|QuickAddField
     */
    public function getAdditionalField($name)
    {
        return array_key_exists($name, $this->additionalFields) ? $this->additionalFields[$name] : null;
    }

    public function addAdditionalField(QuickAddField $field)
    {
        $this->additionalFields[$field->getName()] = $field;
    }
}
