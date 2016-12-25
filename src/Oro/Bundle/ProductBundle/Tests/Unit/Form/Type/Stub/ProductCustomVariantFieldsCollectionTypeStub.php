<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Oro\Bundle\ProductBundle\Form\Type\ProductCustomVariantFieldsCollectionType;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomVariantFieldsCollectionTypeStub extends ProductCustomVariantFieldsCollectionType
{
    /**
     * @var array
     */
    protected $exampleCustomFields = [];

    /**
     * @param array $customFields
     * @param CustomFieldProvider $customFieldProvider
     * @param string $productClass
     */
    public function __construct(array $customFields = [], CustomFieldProvider $customFieldProvider, $productClass)
    {
        $this->exampleCustomFields = $customFields;
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = $productClass;
    }

    /**
     * @return array
     */
    protected function getCustomVariantFields()
    {
        return $this->exampleCustomFields;
    }
}
