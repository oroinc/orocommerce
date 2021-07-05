<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddRowCollection as QuickAddRowCollectionConstraint;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuickAddRowCollectionValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuickAddRowCollectionValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var QuickAddRowCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $constraint;

    /**
     * @var QuickAddRowCollectionValidator
     */
    protected $validator;

    /**
     * @var ValidatorInterface
     */
    protected $validatorInterface;

    protected function setUp(): void
    {
        $this->validatorInterface = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();

        $this->constraint = new QuickAddRowCollectionConstraint();

        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidItemsOfCollection()
    {
        $collection = new QuickAddRowCollection();
        $quickAddRow = new QuickAddRow(1, 'SKU1', 3, 'item');

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $quickAddRow->setProduct($product);

        $collection->add($quickAddRow);

        $violations = new ConstraintViolationList();

        $this->validatorInterface->method('validate')
            ->with($quickAddRow, null)
            ->willReturn($violations);

        $this->validator->validate($collection, $this->constraint);

        $this->assertEquals(1, $collection->getValidRows()->count());
    }

    public function testNotValidItemsOfCollection()
    {
        $collection = new QuickAddRowCollection();
        $quickAddRow = new QuickAddRow(1, 'SKU1', 3, 'item');

        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quickAddRow->setProduct($product);
        $collection->add($quickAddRow);

        $iterator = $this->createMock(\Iterator::class);

        $violation = $this->createMock(ConstraintViolation::class);

        $violationMessage = 'some.message';
        $violation->method('getMessage')
            ->willReturn($violationMessage);

        $violationParameters = ['{{ sku }}' => 'SKU1', '{{ unit }}' => 'item'];
        $violation->method('getParameters')
            ->willReturn($violationParameters);

        $iterator->method('current')
            ->willReturn($violation);

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('getIterator')
            ->willReturn($iterator);

        $violations->method('count')
            ->willReturn(1);

        $this->validatorInterface->method('validate')
            ->with($quickAddRow, null, $this->anything())
            ->willReturn($violations);

        $this->validator->validate($collection, $this->constraint);

        $this->assertEquals(1, $collection->getInvalidRows()->count());
    }

    /**
     * @return QuickAddRowCollectionValidator
     */
    protected function createValidator()
    {
        return new QuickAddRowCollectionValidator($this->validatorInterface);
    }
}
