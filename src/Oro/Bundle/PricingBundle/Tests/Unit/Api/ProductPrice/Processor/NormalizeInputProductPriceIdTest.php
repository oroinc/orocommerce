<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeInputProductPriceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NormalizeInputProductPriceIdTest extends TestCase
{
    /**
     * @var PriceListIDContextStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceListIDContextStorage;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validator;

    /**
     * @var SingleItemContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var NormalizeInputProductPriceId
     */
    private $processor;

    protected function setUp()
    {
        $this->priceListIDContextStorage = $this->createMock(PriceListIDContextStorageInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->context = $this->createMock(SingleItemContext::class);

        $this->processor = new NormalizeInputProductPriceId(
            $this->priceListIDContextStorage,
            $this->doctrineHelper,
            $this->validator
        );
    }

    public function testProcessWrongType()
    {
        $this->priceListIDContextStorage
            ->expects(static::never())
            ->method('store');

        $this->processor->process($this->createMock(ApiContext::class));
    }

    public function testProcessWrongId()
    {
        $this->processor->process($this->context);
    }

    public function testProcessIdNotString()
    {
        $this->context
            ->expects(static::exactly(2))
            ->method('getId')
            ->willReturn(12);

        $this->priceListIDContextStorage
            ->expects(static::never())
            ->method('store');

        $this->processor->process($this->context);
    }

    public function testProcessWrongEntityId()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->context
            ->expects(static::exactly(3))
            ->method('getId')
            ->willReturn('321-weffwfew-43242-fewfewfefw');

        $this->processor->process($this->context);
    }

    public function testProcessWrongUuid()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->context
            ->expects(static::exactly(3))
            ->method('getId')
            ->willReturn('fewfw432432effwefw-12');

        $this->validator
            ->expects(static::once())
            ->method('validate')
            ->willReturn(new ArrayCollection(['error']));

        $this->processor->process($this->context);
    }

    public function testProcessWrongPriceListId()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->context
            ->expects(static::exactly(3))
            ->method('getId')
            ->willReturn('fewfw432432effwefw-12');

        $this->validator
            ->expects(static::once())
            ->method('validate')
            ->willReturn(new ArrayCollection());

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->createMock(EntityRepository::class));

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $id = 'id';
        $priceListId = 12;

        $this->context
            ->expects(static::exactly(3))
            ->method('getId')
            ->willReturn($id . '-' . $priceListId);

        $this->context
            ->expects(static::exactly(1))
            ->method('setId')
            ->with($id);

        $this->validator
            ->expects(static::once())
            ->method('validate')
            ->willReturn(new ArrayCollection());

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('find')
            ->willReturn($priceListId);

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->priceListIDContextStorage
            ->expects(static::once())
            ->method('store')
            ->with($priceListId, $this->context);

        $this->processor->process($this->context);
    }
}
