<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductCustomFieldsChoiceType;

class ProductCustomFieldsChoiceTypeStub extends ProductCustomFieldsChoiceType
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
