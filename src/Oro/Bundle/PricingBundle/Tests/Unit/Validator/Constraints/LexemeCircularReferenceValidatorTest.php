<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Validator\Constraints\LexemeCircularReference;
use Oro\Bundle\PricingBundle\Validator\Constraints\LexemeCircularReferenceValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\RelationNode;
use Oro\Component\Expression\Node\ValueNode;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LexemeCircularReferenceValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject */
    private $parser;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    protected function setUp(): void
    {
        $this->parser = $this->createMock(ExpressionParser::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        $entityManager = $this->createMock(ObjectManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->withAnyParameters()
            ->willReturn($this->entityRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->withAnyParameters()
            ->willReturn($entityManager);

        return new LexemeCircularReferenceValidator($this->parser, $doctrine, PropertyAccess::createPropertyAccessor());
    }

    private function getPriceList(int $id, string $productAssignmentRule = null): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);
        if (null !== $productAssignmentRule) {
            $priceList->setProductAssignmentRule($productAssignmentRule);
        }

        return $priceList;
    }

    private function getPriceRule(
        int $id,
        PriceList $priceList = null,
        string $rule = null,
        string $ruleCondition = null
    ): PriceRule {
        $priceRule = new PriceRule();
        ReflectionUtil::setId($priceRule, $id);
        if (null !== $priceList) {
            $priceRule->setPriceList($priceList);
        }
        if (null !== $rule) {
            $priceRule->setRule($rule);
        }
        if (null !== $ruleCondition) {
            $priceRule->setRuleCondition($ruleCondition);
        }

        return $priceRule;
    }

    public function testValidateSuccess()
    {
        $priceList1 = $this->getPriceList(1, 'priceList[2].productAssignmentRule');
        $priceList2 = $this->getPriceList(2, 'priceList[3].productAssignmentRule');
        $priceList3 = $this->getPriceList(3);

        $this->parser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                [$priceList1->getProductAssignmentRule()],
                [$priceList2->getProductAssignmentRule()]
            )
            ->willReturnOnConsecutiveCalls(
                new NameNode(PriceList::class, 'productAssignmentRule', 2),
                new NameNode(PriceList::class, 'productAssignmentRule', 3)
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([2], [3])
            ->willReturnOnConsecutiveCalls($priceList2, $priceList3);
        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive([['priceList' => 2]], [['priceList' => 3]])
            ->willReturnOnConsecutiveCalls([], []);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->validator->validate($priceList1, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateBinarySuccess()
    {
        $priceList1 = $this->getPriceList(
            1,
            'priceList[2].productAssignmentRule and priceList[3].productAssignmentRule'
        );
        $priceList2 = $this->getPriceList(2, 'priceList[3].productAssignmentRule');
        $priceList3 = $this->getPriceList(3);

        $this->parser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                [$priceList1->getProductAssignmentRule()],
                [$priceList2->getProductAssignmentRule()]
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
            ->withConsecutive([2], [3], [3])
            ->willReturnOnConsecutiveCalls($priceList2, $priceList3, $priceList3);

        $this->entityRepository->expects($this->exactly(3))
            ->method('findBy')
            ->withConsecutive([['priceList' => 2]], [['priceList' => 3]], [['priceList' => 3]])
            ->willReturnOnConsecutiveCalls([], [], []);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->validator->validate($priceList1, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithPriceRuleSuccess()
    {
        $priceList1 = $this->getPriceList(1, 'priceList[2].productAssignmentRule');
        $priceList2 = $this->getPriceList(2);
        $priceList3 = $this->getPriceList(3);

        $priceRule1 = $this->getPriceRule(1, $priceList2, 'pricelist[3].productAssignmentRule');
        $priceRule2 = $this->getPriceRule(
            2,
            $priceList2,
            null,
            'product.id in pricelist[3].assignedProducts or pricelist[3].prices.quantity > 10'
        );

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                [$priceList1->getProductAssignmentRule()],
                [$priceRule1->getRule()],
                [$priceRule2->getRuleCondition()]
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
            ->withConsecutive([2], [3])
            ->willReturnOnConsecutiveCalls($priceList2, $priceList3);
        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive([['priceList' => 2]], [['priceList' => 3]])
            ->willReturnOnConsecutiveCalls([$priceRule1, $priceRule2], []);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->validator->validate($priceList1, $constraint);

        $this->assertNoViolation();
    }

    public function testValidatePriceRuleSuccess()
    {
        $priceList1 = $this->getPriceList(1, 'pricelist[3].productAssignmentRule');
        $priceList2 = $this->getPriceList(2);
        $priceList3 = $this->getPriceList(3);

        $priceRule1 = $this->getPriceRule(1, $priceList1, 'pricelist[2].productAssignmentRule');
        $priceRule2 = $this->getPriceRule(
            2,
            $priceList2,
            null,
            'product.id in pricelist[3].assignedProducts or pricelist[3].prices.quantity > 10'
        );
        $priceRule3 = $this->getPriceRule(3, $priceList3);

        $this->parser->expects($this->exactly(2))
            ->method('parse')
            ->withConsecutive(
                [$priceRule1->getRule()],
                [$priceRule2->getRuleCondition()]
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
            ->withConsecutive([2], [3])
            ->willReturnOnConsecutiveCalls($priceList2, $priceList3);
        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive([['priceList' => 2]], [['priceList' => 3]])
            ->willReturnOnConsecutiveCalls([$priceRule2], [$priceRule3]);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['rule'];
        $this->validator->validate($priceRule1, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateFailed()
    {
        $priceList1 = $this->getPriceList(1, 'priceList[2].productAssignmentRule');
        $priceList2 = $this->getPriceList(2, 'priceList[3].productAssignmentRule');
        $priceList3 = $this->getPriceList(3, 'priceList[1].productAssignmentRule');

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                [$priceList1->getProductAssignmentRule()],
                [$priceList2->getProductAssignmentRule()],
                [$priceList3->getProductAssignmentRule()]
            )
            ->willReturnOnConsecutiveCalls(
                new NameNode(PriceList::class, 'productAssignmentRule', 2),
                new NameNode(PriceList::class, 'productAssignmentRule', 3),
                new NameNode(PriceList::class, 'productAssignmentRule', 1)
            );

        $this->entityRepository->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive([2], [3])
            ->willReturnOnConsecutiveCalls($priceList2, $priceList3);
        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive([['priceList' => 2]], [['priceList' => 3]])
            ->willReturnOnConsecutiveCalls([], []);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->validator->validate($priceList1, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.productAssignmentRule')
            ->assertRaised();
    }

    public function testValidateBinaryFailed()
    {
        $priceList1 = $this->getPriceList(
            1,
            'priceList[2].productAssignmentRule and priceList[3].productAssignmentRule'
        );
        $priceList2 = $this->getPriceList(2, 'priceList[3].productAssignmentRule');
        $priceList3 = $this->getPriceList(3, 'priceList[1].productAssignmentRule');

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                [$priceList1->getProductAssignmentRule()],
                [$priceList2->getProductAssignmentRule()],
                [$priceList3->getProductAssignmentRule()]
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
            ->withConsecutive([2], [3])
            ->willReturnOnConsecutiveCalls($priceList2, $priceList3);
        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive([['priceList' => 2]], [['priceList' => 3]])
            ->willReturnOnConsecutiveCalls([], []);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];
        $this->validator->validate($priceList1, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.productAssignmentRule')
            ->assertRaised();
    }

    public function testValidatePriceRuleWithoutPriceListFailed()
    {
        $priceRule = new PriceRule();

        $this->parser->expects(self::never())
            ->method('parse');

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['productAssignmentRule'];

        $this->validator->validate($priceRule, $constraint);
    }

    public function testValidatePriceRuleFailed()
    {
        $priceList1 = $this->getPriceList(1, 'pricelist[3].productAssignmentRule');
        $priceList2 = $this->getPriceList(2);
        $priceList3 = $this->getPriceList(3);

        $priceRule1 = $this->getPriceRule(1, $priceList1, 'pricelist[2].productAssignmentRule');
        $priceRule2 = $this->getPriceRule(
            2,
            $priceList2,
            null,
            'product.id in pricelist[3].assignedProducts or pricelist[3].prices.quantity > 10'
        );
        $priceRule3 = $this->getPriceRule(3, $priceList3, 'pricelist[1].productAssignmentRule');

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive(
                [$priceRule1->getRule()],
                [$priceRule2->getRuleCondition()],
                [$priceRule3->getRule()]
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
            ->withConsecutive([2], [3])
            ->willReturnOnConsecutiveCalls($priceList2, $priceList3);
        $this->entityRepository->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive([['priceList' => 2]], [['priceList' => 3]])
            ->willReturnOnConsecutiveCalls([$priceRule2], [$priceRule3]);

        $constraint = new LexemeCircularReference();
        $constraint->fields = ['rule'];
        $this->validator->validate($priceRule1, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.rule')
            ->assertRaised();
    }
}
