<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use OroB2B\Bundle\SaleBundle\Validator\Constraints\QuoteProductItems;
use OroB2B\Bundle\SaleBundle\Validator\Constraints\QuoteProductItemsValidator;

class QuoteProductItemsTest extends \PHPUnit_Framework_TestCase
{
    /** @var QuoteProductItems */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var QuoteProductItemsValidator */
    protected $validator;

    protected function setUp()
    {
        $this->context      = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint   = new QuoteProductItems();
        $this->validator    = new QuoteProductItemsValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'orob2b_sale.validator.quote_product_unit',
            $this->constraint->validatedBy()
        );

        $this->assertEquals([Constraint::PROPERTY_CONSTRAINT], $this->constraint->getTargets());
    }
}
