<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Validator;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\QuickAddRowCollection as QuickAddRowCollectionConstraint;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\QuickAddRowCollectionValidator;

class QuickAddRowCollectionValidatorTest extends \PHPUnit_Framework_TestCase
{
    const SKU_1 = 'ABC';
    const SKU_2 = 'DEF';
    const SKU_3 = 'GHI';

    /**
     * @var QuickAddRowCollectionValidator
     */
    protected $validator;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->repository = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BProductBundle:Product')
            ->willReturn($this->repository);

        $this->validator = new QuickAddRowCollectionValidator($this->registry);
    }

    public function testValidate()
    {
        $this->repository->expects($this->at(0))
            ->method('findOneBySku')
            ->with(self::SKU_1)
            ->willReturn(new Product());
        $this->repository->expects($this->at(1))
            ->method('findOneBySku')
            ->with(self::SKU_2)
            ->willReturn(null);
        $this->repository->expects($this->exactly(2))
            ->method('findOneBySku');

        $collection = new QuickAddRowCollection();
        $collection->add(new QuickAddRow(1, self::SKU_1, 2));
        $collection->add(new QuickAddRow(2, self::SKU_2, 1));
        $collection->add(new QuickAddRow(3, self::SKU_3, null));

        $this->validator->validate($collection, new QuickAddRowCollectionConstraint());

        $this->assertTrue($collection[0]->isValid());
        $this->assertFalse($collection[1]->isValid());
        $this->assertFalse($collection[2]->isValid());
    }
}
