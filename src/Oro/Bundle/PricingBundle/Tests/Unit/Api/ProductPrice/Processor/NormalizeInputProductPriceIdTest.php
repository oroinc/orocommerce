<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\NormalizeInputProductPriceId;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class NormalizeInputProductPriceIdTest extends GetProcessorTestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var NormalizeInputProductPriceId */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->processor = new NormalizeInputProductPriceId($this->doctrineHelper, $this->validator);
    }

    public function testProcessWrongId()
    {
        $this->processor->process($this->context);
    }

    public function testProcessIdNotString()
    {
        $this->context->setId(12);
        $this->processor->process($this->context);
        self::assertFalse($this->context->has('price_list_id'));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testProcessWrongEntityId()
    {
        $this->context->setId('321-weffwfew-43242-fewfewfefw');
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testProcessWrongUuid()
    {
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(new ArrayCollection(['error']));

        $this->context->setId('fewfw432432effwefw-12');
        $this->processor->process($this->context);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testProcessWrongPriceListId()
    {
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(new ArrayCollection());

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->createMock(EntityRepository::class));

        $this->context->setId('fewfw432432effwefw-12');
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $id = 'id';
        $priceListId = 12;

        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn(new ArrayCollection());

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->willReturn($priceListId);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $this->context->setId($id . '-' . $priceListId);
        $this->processor->process($this->context);
        self::assertSame($id, $this->context->getId());
        self::assertSame($priceListId, $this->context->get('price_list_id'));
    }
}
