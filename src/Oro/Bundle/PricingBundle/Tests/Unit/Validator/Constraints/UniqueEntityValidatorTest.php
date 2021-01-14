<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueEntityValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UniqueEntityValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UniqueEntity
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var UniqueEntityValidator
     */
    protected $validator;

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var Registry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new UniqueEntity();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shardManager = $this->getMockBuilder(ShardManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new UniqueEntityValidator($this->registry, $this->shardManager);
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_pricing_unique_entity_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testNotExpectedValueException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'must be instance of "Oro\Bundle\PricingBundle\Entity\ProductPrice", "stdClass" given'
        );

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWithAllowedPrice()
    {
        $priceList = static::createMock(PriceList::class);
        $priceList->method('getId')->willReturn(1);
        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        $this->constraint->fields = ['priceList'];
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $metadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();

        $repo = $this->getMockBuilder(EntityRepository::class)
            ->setMethods(['findByPriceList'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects(self::once())->method('getRepository')
            ->with($this->equalTo(ProductPrice::class))
            ->will($this->returnValue($repo));
        $repo->expects(self::once())
            ->method('findByPriceList')
            ->will($this->returnValue([]));

        $metadata
            ->expects(self::any())
            ->method('hasField')
            ->will($this->returnValue(true));
        $metadata
            ->expects(self::any())
            ->method('hasAssociation');

        $refl = $this->createMock(\ReflectionProperty::class);
        $refl->expects(self::any())
            ->method('getValue')
            ->will($this->returnValue($priceList));
        $metadata->reflFields = ['priceList' => $refl];
        $em->expects(self::once())->method('getClassMetadata')
            ->with($this->equalTo(ProductPrice::class))
            ->will($this->returnValue($metadata));

        $this->registry->expects(self::once())->method('getManager')
            ->will($this->returnValue($em));

        $this->context->expects(static::never())->method('buildViolation');
        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testValidateWithoutPriceList()
    {
        $productPrice = new ProductPrice();
        $product = static::createMock(Product::class);
        $product->method('getId')->willReturn(1);
        $productPrice->setProduct($product);
        $this->context->expects(static::never())->method('buildViolation');
        $this->validator->validate($productPrice, $this->constraint);
    }
}
