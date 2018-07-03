<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\ProductPageTemplate;

class ProductPageTemplateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductPageTemplate
     */
    protected $constraint;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductPageTemplate();
    }

    public function testValidatedBy()
    {
        $this->assertEquals('oro_product_page_template_validator', $this->constraint->validatedBy());
    }
}
