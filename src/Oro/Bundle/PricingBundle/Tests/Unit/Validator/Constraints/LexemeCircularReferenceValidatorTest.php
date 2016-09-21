<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Expression\BinaryNode;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Oro\Bundle\PricingBundle\Expression\ValueNode;
use Oro\Bundle\PricingBundle\Validator\Constraints\LexemeCircularReference;
use Oro\Bundle\PricingBundle\Validator\Constraints\LexemeCircularReferenceValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */

class LexemeCircularReferenceValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var LexemeCircularReferenceValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->context = $this->getMock(ExecutionContextInterface::class);
        $this->parser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMockBuilder(RegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new LexemeCircularReferenceValidator($this->parser, $this->doctrine);
        $this->validator->initialize($this->context);
    }

    public function testValidateSuccess()
    {
        $priceList1 = $this->getEntity(
            PriceList::class,
            ['id' => 1, 'productAssignmentRule' => 'priceList[2].productAssignmentRule']
        );
        $priceList2 = $this->getEntity(
            PriceList::class,
            ['id' => 2, 'productAssignmentRule' => 'priceList[3].productAssignmentRule']
        );
        $priceList3 = $this->getEntity(
            PriceList::class,
            ['id' => 3, 'productAssignmentRule' => null]
        );

        $this->parser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($priceList1->getProductAssignmentRule()),
                $this->equalTo($priceList2->getProductAssignmentRule())
            )
            ->willReturnOnConsecutiveCalls(
                new NameNode(PriceList::class, 'productAssignmentRule', 2),
                new NameNode(PriceList::class, 'productAssignmentRule', 3)
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $priceList2,
                $priceList3
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                [],
                []
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($priceList1, $constraint);
    }

    public function testValidateBinarySuccess()
    {
        $priceList1 = $this->getEntity(
            PriceList::class,
            [
                'id' => 1,
                'productAssignmentRule' => 'priceList[2].productAssignmentRule and priceList[3].productAssignmentRule'
            ]
        );
        $priceList2 = $this->getEntity(
            PriceList::class,
            ['id' => 2, 'productAssignmentRule' => 'priceList[3].productAssignmentRule']
        );
        $priceList3 = $this->getEntity(
            PriceList::class,
            ['id' => 3, 'productAssignmentRule' => null]
        );

        $this->parser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($priceList1->getProductAssignmentRule()),
                $this->equalTo($priceList2->getProductAssignmentRule())
            )
            ->willReturnOnConsecutiveCalls(
                new BinaryNode(
                    new NameNode(PriceList::class, 'productAssignmentRule', 2),
                    new NameNode(PriceList::class, 'productAssignmentRule', 3),
                    'and'
                ),
                new NameNode(PriceList::class, 'productAssignmentRule', 3)
            );

        $this->entityRepository->expects($this->exactly(3))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $priceList2,
                $priceList3,
                $priceList3
            );

        $this->entityRepository->expects($this->exactly(3))
            ->method('findBy')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [],
                []
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($priceList1, $constraint);
    }

    public function testValidateWithPriceRuleSuccess()
    {
        $priceList1 = $this->getEntity(
            PriceList::class,
            ['id' => 1, 'productAssignmentRule' => 'priceList[2].productAssignmentRule']
        );
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);

        $priceRule1 = $this->getEntity(
            PriceRule::class,
            ['id' => 1, 'priceList' => $priceList2, 'rule' => 'pricelist[3].productAssignmentRule']
        );
        $priceRule2 = $this->getEntity(
            PriceRule::class,
            [
                'id' => 2,
                'priceList' => $priceList2,
                'ruleCondition' => 'product.id in pricelist[3].assignedProducts or pricelist[3].prices.quantity > 10'
            ]
        );

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($priceList1->getProductAssignmentRule()),
                $this->equalTo($priceList2->getProductAssignmentRule()),
                $this->equalTo($priceList2->getProductAssignmentRule())
            )
            ->willReturnOnConsecutiveCalls(
                new NameNode(PriceList::class, 'productAssignmentRule', 2),
                new NameNode(PriceList::class, 'productAssignmentRule', 3),
                new BinaryNode(
                    new BinaryNode(
                        new NameNode(Product::class, 'id', null),
                        new NameNode(PriceList::class, 'assignedProducts', 3),
                        'in'
                    ),
                    new BinaryNode(
                        new RelationNode(PriceList::class, 'prices', 'quantity', 3),
                        new ValueNode(10),
                        '>'
                    ),
                    'or'
                )
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $priceList2,
                $priceList3
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3),
                $this->equalTo(3),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                [$priceRule1, $priceRule2],
                [],
                [],
                []
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($priceList1, $constraint);
    }

    public function testValidatePriceRuleSuccess()
    {
        $priceList1 = $this->getEntity(
            PriceList::class,
            ['id' => 1, 'productAssignmentRule' => 'pricelist[3].productAssignmentRule']
        );
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);

        $priceRule1 = $this->getEntity(
            PriceRule::class,
            ['id' => 1, 'priceList' => $priceList1, 'rule' => 'pricelist[2].productAssignmentRule']
        );
        $priceRule2 = $this->getEntity(
            PriceRule::class,
            [
                'id' => 2,
                'priceList' => $priceList2,
                'ruleCondition' => 'product.id in pricelist[3].assignedProducts or pricelist[3].prices.quantity > 10'
            ]
        );
        $priceRule3 = $this->getEntity(
            PriceRule::class,
            [
                'id' => 3,
                'priceList' => $priceList3,
            ]
        );

        $this->parser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($priceRule1->getRule()),
                $this->equalTo($priceRule2->getRuleCondition())
            )
            ->willReturnOnConsecutiveCalls(
                new NameNode(PriceList::class, 'productAssignmentRule', 2),
                new BinaryNode(
                    new BinaryNode(
                        new NameNode(Product::class, 'id', null),
                        new NameNode(PriceList::class, 'assignedProducts', 3),
                        'in'
                    ),
                    new BinaryNode(
                        new RelationNode(PriceList::class, 'prices', 'quantity', 3),
                        new ValueNode(10),
                        '>'
                    ),
                    'or'
                )
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $priceList2,
                $priceList3
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                [$priceRule2],
                [$priceRule3]
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['rule'];
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($priceRule1, $constraint);
    }

    public function testValidateFailed()
    {
        $priceList1 = $this->getEntity(
            PriceList::class,
            ['id' => 1, 'productAssignmentRule' => 'priceList[2].productAssignmentRule']
        );
        $priceList2 = $this->getEntity(
            PriceList::class,
            ['id' => 2, 'productAssignmentRule' => 'priceList[3].productAssignmentRule']
        );
        $priceList3 = $this->getEntity(
            PriceList::class,
            ['id' => 3, 'productAssignmentRule' => 'priceList[1].productAssignmentRule']
        );

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($priceList1->getProductAssignmentRule()),
                $this->equalTo($priceList2->getProductAssignmentRule()),
                $this->equalTo($priceList3->getProductAssignmentRule())
            )
            ->willReturnOnConsecutiveCalls(
                new NameNode(PriceList::class, 'productAssignmentRule', 2),
                new NameNode(PriceList::class, 'productAssignmentRule', 3),
                new NameNode(PriceList::class, 'productAssignmentRule', 1)
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $priceList2,
                $priceList3
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                [],
                []
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('productAssignmentRule')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $this->validator->validate($priceList1, $constraint);
    }

    public function testValidateBinaryFailed()
    {
        $priceList1 = $this->getEntity(
            PriceList::class,
            [
                'id' => 1,
                'productAssignmentRule' => 'priceList[2].productAssignmentRule and priceList[3].productAssignmentRule'
            ]
        );
        $priceList2 = $this->getEntity(
            PriceList::class,
            ['id' => 2, 'productAssignmentRule' => 'priceList[3].productAssignmentRule']
        );
        $priceList3 = $this->getEntity(
            PriceList::class,
            ['id' => 3, 'productAssignmentRule' => 'priceList[1].productAssignmentRule']
        );

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($priceList1->getProductAssignmentRule()),
                $this->equalTo($priceList2->getProductAssignmentRule()),
                $this->equalTo($priceList3->getProductAssignmentRule())
            )
            ->willReturnOnConsecutiveCalls(
                new BinaryNode(
                    new NameNode(PriceList::class, 'productAssignmentRule', 2),
                    new NameNode(PriceList::class, 'productAssignmentRule', 3),
                    'and'
                ),
                new NameNode(PriceList::class, 'productAssignmentRule', 3),
                new NameNode(PriceList::class, 'productAssignmentRule', 1)
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $priceList2,
                $priceList3
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                [],
                []
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('productAssignmentRule')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $this->validator->validate($priceList1, $constraint);
    }

    public function testValidatePriceRuleFailed()
    {
        $priceList1 = $this->getEntity(
            PriceList::class,
            ['id' => 1, 'productAssignmentRule' => 'pricelist[3].productAssignmentRule']
        );
        $priceList2 = $this->getEntity(PriceList::class, ['id' => 2]);
        $priceList3 = $this->getEntity(PriceList::class, ['id' => 3]);

        $priceRule1 = $this->getEntity(
            PriceRule::class,
            ['id' => 1, 'priceList' => $priceList1, 'rule' => 'pricelist[2].productAssignmentRule']
        );
        $priceRule2 = $this->getEntity(
            PriceRule::class,
            [
                'id' => 2,
                'priceList' => $priceList2,
                'ruleCondition' => 'product.id in pricelist[3].assignedProducts or pricelist[3].prices.quantity > 10'
            ]
        );
        $priceRule3 = $this->getEntity(
            PriceRule::class,
            [
                'id' => 3,
                'priceList' => $priceList3,
                'rule' => 'pricelist[1].productAssignmentRule'
            ]
        );

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                $this->equalTo($priceRule1->getRule()),
                $this->equalTo($priceRule2->getRuleCondition()),
                $this->equalTo($priceRule3->getRule())
            )
            ->willReturnOnConsecutiveCalls(
                new NameNode(PriceList::class, 'productAssignmentRule', 2),
                new BinaryNode(
                    new BinaryNode(
                        new NameNode(Product::class, 'id', null),
                        new NameNode(PriceList::class, 'assignedProducts', 3),
                        'in'
                    ),
                    new BinaryNode(
                        new RelationNode(PriceList::class, 'prices', 'quantity', 3),
                        new ValueNode(10),
                        '>'
                    ),
                    'or'
                ),
                new NameNode(PriceList::class, 'productAssignmentRule', 1)
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                $priceList2,
                $priceList3
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                $this->equalTo(2),
                $this->equalTo(3)
            )
            ->willReturnOnConsecutiveCalls(
                [$priceRule2],
                [$priceRule3]
            );

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($this->entityManager);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['rule'];
        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('rule')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $this->context->expects($this->any())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $this->validator->validate($priceRule1, $constraint);
    }
}
