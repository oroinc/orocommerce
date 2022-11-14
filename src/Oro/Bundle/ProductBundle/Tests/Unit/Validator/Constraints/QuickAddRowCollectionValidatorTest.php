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
    /** @var QuickAddRowCollection|\PHPUnit\Framework\MockObject\MockObject */
    protected $constraint;

    /** @var QuickAddRowCollectionValidator */
    protected $validator;

    /** @var ValidatorInterface */
    private $validatorInterface;

    protected function setUp(): void
    {
        $this->validatorInterface = $this->createMock(ValidatorInterface::class);

        parent::setUp();

        $this->constraint = new QuickAddRowCollectionConstraint();

        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    protected function createValidator(): QuickAddRowCollectionValidator
    {
        return new QuickAddRowCollectionValidator($this->validatorInterface);
    }

    public function testValidItemsOfCollection()
    {
        $collection = new QuickAddRowCollection();
        $quickAddRow = new QuickAddRow(1, 'SKU1', 3, 'item');

        $product = $this->createMock(Product::class);

        $quickAddRow->setProduct($product);

        $collection->add($quickAddRow);

        $violations = new ConstraintViolationList();

        $this->validatorInterface->expects(self::any())
            ->method('validate')
            ->with($quickAddRow, null)
            ->willReturn($violations);

        $this->validator->validate($collection, $this->constraint);

        $this->assertEquals(1, $collection->getValidRows()->count());
    }

    public function testNotValidItemsOfCollection()
    {
        $collection = new QuickAddRowCollection();
        $quickAddRow = new QuickAddRow(1, 'SKU1', 3, 'item');

        $product = $this->createMock(Product::class);
        $quickAddRow->setProduct($product);
        $collection->add($quickAddRow);

        $iterator = $this->createMock(\Iterator::class);

        $violation = $this->createMock(ConstraintViolation::class);

        $violationMessage = 'some.message';
        $violation->expects(self::any())
            ->method('getMessage')
            ->willReturn($violationMessage);

        $violationParameters = ['{{ sku }}' => 'SKU1', '{{ unit }}' => 'item'];
        $violation->expects(self::any())
            ->method('getParameters')
            ->willReturn($violationParameters);

        $iterator->expects(self::any())
            ->method('current')
            ->willReturn($violation);

        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->expects(self::any())
            ->method('getIterator')
            ->willReturn($iterator);

        $violations->expects(self::any())
            ->method('count')
            ->willReturn(1);

        $this->validatorInterface->expects(self::any())
            ->method('validate')
            ->with($quickAddRow, null, $this->anything())
            ->willReturn($violations);

        $this->validator->validate($collection, $this->constraint);

        $this->assertEquals(1, $collection->getInvalidRows()->count());
    }
}
