<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\EventListener\DoctrineFlushProgressListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxValueManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const TAX_VALUE_CLASS = TaxValue::class;
    private const TAX_CLASS = Tax::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var DoctrineFlushProgressListener|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineFlushProgressListener;

    /** @var TaxValueManager */
    private $manager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->doctrineFlushProgressListener = $this->createMock(DoctrineFlushProgressListener::class);

        $this->manager = new TaxValueManager(
            $this->doctrineHelper,
            $this->doctrineFlushProgressListener,
            self::TAX_VALUE_CLASS,
            self::TAX_CLASS
        );
    }

    public function testGetTaxValue()
    {
        $class = self::TAX_VALUE_CLASS;
        $id = 1;
        $taxValue = $this->getEntity(TaxValue::class, ['id' => 1]);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(
                self::logicalAnd(
                    self::isType('array'),
                    self::containsEqual($class),
                    self::containsEqual($id)
                )
            )
            ->willReturn($taxValue);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));

        // cache
        $this->assertSame($taxValue, $this->manager->getTaxValue($class, $id));
    }

    public function testGetTaxValueNew()
    {
        $class = self::TAX_VALUE_CLASS;
        $id = 1;

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::exactly(2))
            ->method('findOneBy')
            ->with(
                self::logicalAnd(
                    self::isType('array'),
                    self::containsEqual($class),
                    self::containsEqual($id)
                )
            )
            ->willReturn(null);

        $this->doctrineHelper->expects(self::exactly(2))
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $taxValue = $this->manager->getTaxValue($class, $id);
        $this->assertInstanceOf(self::TAX_VALUE_CLASS, $taxValue);

        // Since taxes can be different with each request for their creation and not managed in UoW, we cannot
        // guarantee that cache of taxes will be relevant for every request to get tax values. In this case, it makes
        // not sense to cache tax values. Actual taxes are taxes that have been flashed and have identifier value.
        $this->manager->getTaxValue($class, $id);
    }

    /**
     * @dataProvider saveTaxValueProvider
     */
    public function testSaveTaxValue(bool $flushInProgress)
    {
        $taxValue = new TaxValue();

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($taxValue);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($em);

        $this->doctrineFlushProgressListener->expects($this->once())
            ->method('isFlushInProgress')
            ->with($em)
            ->willReturn($flushInProgress);

        $classMetadata = $this->createMock(ClassMetadata::class);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->exactly((int)$flushInProgress))
            ->method('computeChangeSet')
            ->with($classMetadata, $taxValue);

        $em->expects($this->exactly((int)$flushInProgress))
            ->method('getClassMetadata')
            ->with(TaxValue::class)
            ->willReturn($classMetadata);

        $em->expects($this->exactly((int)$flushInProgress))
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->manager->saveTaxValue($taxValue);
    }

    public function saveTaxValueProvider(): array
    {
        return [
            'flush in progress' => [
                'flushInProgress' => true,
            ],
            'flush not in progress' => [
                'flushInProgress' => false,
            ],
        ];
    }

    /**
     * @dataProvider flushTaxValueIfAllowedDataProvider
     */
    public function testFlushTaxValueIfAllowed(bool $flushInProgress, bool $flushExpected)
    {
        $em = $this->createMock(EntityManager::class);
        $em->expects($this->exactly((int)$flushExpected))
            ->method('flush');

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($em);

        $this->doctrineFlushProgressListener->expects($this->once())
            ->method('isFlushInProgress')
            ->with($em)
            ->willReturn($flushInProgress);

        $this->manager->flushTaxValueIfAllowed();
    }

    public function flushTaxValueIfAllowedDataProvider(): array
    {
        return [
            'flush not in progress' => [
                'flushInProgress' => false,
                'flushExpected' => true,
            ],
            'flush in progress' => [
                'flushInProgress' => true,
                'flushExpected' => false,
            ],
        ];
    }

    public function testProxyGetReference()
    {
        $code = 'code';

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'code']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(self::TAX_CLASS)
            ->willReturn($repo);

        $this->manager->getTax($code);
    }

    public function testClear()
    {
        $class = 'stdClass';
        $id = 1;
        $cachedTaxValue = $this->getEntity(TaxValue::class, ['id' => 1]);
        $notCachedTaxValue = $this->getEntity(TaxValue::class, ['id' => 2]);

        $repository = $this->createMock(ObjectRepository::class);

        $repository->expects(self::exactly(2))
            ->method('findOneBy')
            ->with(
                self::logicalAnd(
                    self::isType('array'),
                    self::containsEqual($class),
                    self::containsEqual($id)
                )
            )
            ->willReturnOnConsecutiveCalls($cachedTaxValue, $notCachedTaxValue);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->assertSame($cachedTaxValue, $this->manager->getTaxValue($class, $id));
        $this->assertSame($cachedTaxValue, $this->manager->getTaxValue($class, $id));
        $this->manager->clear();
        $this->assertSame($notCachedTaxValue, $this->manager->getTaxValue($class, $id));
    }

    /**
     * @dataProvider removeTaxValueProvider
     */
    public function testRemoveTaxValue(bool $flush, bool $contains, bool $expectedResult)
    {
        $taxValue = new TaxValue();

        $taxValueEm = $this->createMock(EntityManager::class);
        $taxValueEm->expects($this->once())
            ->method('contains')
            ->with($taxValue)
            ->willReturn($contains);
        $taxValueEm->expects($contains ? $this->once() : $this->never())
            ->method('remove')
            ->with($taxValue);
        $taxValueEm->expects($contains && $flush ? $this->once() : $this->never())
            ->method('flush')
            ->with($taxValue);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($taxValueEm);

        $this->assertEquals($expectedResult, $this->manager->removeTaxValue($taxValue, $flush));
    }

    public function removeTaxValueProvider(): array
    {
        return [
            [
                'flush' => true,
                'contains' => false,
                'expectedResult' => false,
            ],
            [
                'flush' => true,
                'contains' => true,
                'expectedResult' => true,
            ],
            [
                'flush' => false,
                'contains' => true,
                'expectedResult' => true,
            ],
        ];
    }

    public function testPreloadTaxValues()
    {
        $entityClass = 'SomeClass';
        $entityIds = [1, 2];
        $taxValue1 = $this->getEntity(TaxValue::class, ['id' => 1, 'entityId' => 1]);
        $taxValue2 = $this->getEntity(TaxValue::class, ['id' => 2, 'entityId' => 2]);
        $taxValues = [$taxValue1, $taxValue2];

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['entityClass' => $entityClass, 'entityId' => $entityIds])
            ->willReturn($taxValues);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(self::TAX_VALUE_CLASS)
            ->willReturn($repository);

        $this->manager->preloadTaxValues($entityClass, $entityIds);

        $this->assertEquals($taxValue1, $this->manager->getTaxValue($entityClass, 1));
        $this->assertEquals($taxValue2, $this->manager->getTaxValue($entityClass, 2));

        $this->manager->preloadTaxValues($entityClass, $entityIds);
    }
}
