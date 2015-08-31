<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductCustomFieldsChoiceType;

class StubProductCustomFieldsChoiceType extends ProductCustomFieldsChoiceType
{
    /**
     * @var array
     */
    protected $exampleCustomFields = [];

    /**
     * @param array $customFields
     */
    public function __construct(array $customFields = [])
    {
        $this->exampleCustomFields = $customFields;
    }

    /**
     * @return array
     */
    protected function getProductCustomFields()
    {
        return $this->exampleCustomFields;
    }
}
