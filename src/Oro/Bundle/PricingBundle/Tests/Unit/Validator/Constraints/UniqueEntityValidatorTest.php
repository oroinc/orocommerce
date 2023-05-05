<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueEntity;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniqueEntityValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueEntityValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shardManager = $this->createMock(ShardManager::class);
        parent::setUp();
    }

    protected function createValidator(): UniqueEntityValidator
    {
        return new UniqueEntityValidator($this->doctrine, $this->shardManager);
    }

    public function testNotExpectedValueException()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new UniqueEntity();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateWithAllowedPrice()
    {
        $priceList = $this->createMock(PriceList::class);
        $priceList->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        $em = $this->createMock(EntityManager::class);
        $metadata = $this->createMock(ClassMetadata::class);

        $repo = $this->createMock(BaseProductPriceRepository::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findByPriceList')
            ->willReturn([]);

        $metadata->expects(self::any())
            ->method('hasField')
            ->willReturn(true);
        $metadata->expects(self::any())
            ->method('hasAssociation');

        $refl = $this->createMock(\ReflectionProperty::class);
        $refl->expects(self::any())
            ->method('getValue')
            ->willReturn($priceList);
        $metadata->reflFields = ['priceList' => $refl];
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with(ProductPrice::class)
            ->willReturn($metadata);

        $this->doctrine->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $constraint = new UniqueEntity();
        $constraint->fields = ['priceList'];
        $this->validator->validate($productPrice, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithoutPriceList()
    {
        $productPrice = new ProductPrice();
        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getId')
            ->willReturn(1);
        $productPrice->setProduct($product);

        $constraint = new UniqueEntity();
        $this->validator->validate($productPrice, $constraint);

        $this->assertNoViolation();
    }
}
