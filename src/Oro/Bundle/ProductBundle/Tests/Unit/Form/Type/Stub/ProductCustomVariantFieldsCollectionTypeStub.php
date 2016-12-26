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
     * @param CustomFieldProvider $customFieldProvider
     * @param string $productClass
     * @param array $customFields
     */
    public function __construct(CustomFieldProvider $customFieldProvider, $productClass, array $customFields = [])
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
