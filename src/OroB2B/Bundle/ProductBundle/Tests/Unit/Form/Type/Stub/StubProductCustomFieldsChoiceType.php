<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductCustomFieldsChoiceType;

class StubProductCustomFieldsChoiceType extends AbstractType
{
    /**
     * @return string
     */
    public function getName()
    {
        return ProductCustomFieldsChoiceType::NAME;
    }
}
