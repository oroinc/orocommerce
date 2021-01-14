<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Normalizer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PromotionBundle\Normalizer\ScopeNormalizer;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ScopeNormalizerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var ScopeNormalizer
     */
    protected $normalizer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->normalizer = new ScopeNormalizer($this->registry);
    }

    public function testNormalize()
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 42]);
        $expected = ['id' => 42];

        $actual = $this->normalizer->normalize($scope);

        $this->assertEquals($expected, $actual);
    }

    public function testDenormalize()
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class, ['id' => 42]);

        $scopeData = ['id' => 42];

        $em = $this->createMock(ObjectManager::class);

        $em->expects($this->once())
            ->method('find')
            ->willReturn($scope);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $actual = $this->normalizer->denormalize($scopeData);

        $this->assertEquals($scope, $actual);
    }

    public function testDenormalizeWithoutScope()
    {
        $em = $this->createMock(ObjectManager::class);

        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->assertNull($this->normalizer->denormalize(['id' => 123]));
    }

    public function testRequiredOptionsException()
    {
        $scopeData = [];

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "id" is missing.');

        $this->normalizer->denormalize($scopeData);
    }

    public function testInvalidArgumentException()
    {
        $object = new \stdClass();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument scope should be instance of Scope entity');

        $this->normalizer->normalize($object);
    }
}
