<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

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
    protected function setUp(): void
    {
        $this->constraint = new ProductPageTemplate();
    }

    public function testValidatedBy()
    {
        $this->assertEquals('oro_product_page_template_validator', $this->constraint->validatedBy());
    }
}
