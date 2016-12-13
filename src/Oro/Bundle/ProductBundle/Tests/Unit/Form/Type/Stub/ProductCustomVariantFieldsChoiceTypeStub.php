<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsChoiceType;

class ProductCustomVariantFieldsChoiceTypeStub extends ProductCustomVariantFieldsChoiceType
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
    protected function getCustomVariantFields()
    {
        return $this->exampleCustomFields;
    }
}
