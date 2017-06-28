<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Reflection\StaticReflectionParser;
use Doctrine\Common\Reflection\StaticReflectionProperty;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueEntityValidator;

class UniqueEntityValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UniqueEntity
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
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
     * @var Registry
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
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
    protected function tearDown()
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage must be instance of "Oro\Bundle\PricingBundle\Entity\ProductPrice", "stdClass" given
     */
    public function testNotExpectedValueException()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWithAllowedPrice()
    {
        $priceList = new PriceList();
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

        $reflParser = $this->getMockBuilder(StaticReflectionParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $refl = $this->getMockBuilder(StaticReflectionProperty::class)
            ->setConstructorArgs([$reflParser, $priceList])
            ->setMethods(['getValue'])
            ->getMock();
        $refl->expects(self::any())
            ->method('getValue')
            ->will($this->returnValue($priceList));
        $metadata->reflFields = ['priceList' => $refl];
        $em->expects(self::once())->method('getClassMetadata')
            ->with($this->equalTo(ProductPrice::class))
            ->will($this->returnValue($metadata));

        $this->registry->expects(self::once())->method('getManager')
            ->will($this->returnValue($em));

        $this->validator->validate($productPrice, $this->constraint);
    }
}
