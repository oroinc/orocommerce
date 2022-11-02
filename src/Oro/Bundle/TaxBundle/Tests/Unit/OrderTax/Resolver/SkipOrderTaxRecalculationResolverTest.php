<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\OrderTax\Resolver\SkipOrderTaxRecalculationResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SkipOrderTaxRecalculationResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var TaxManager|\PHPUnit\Framework\MockObject\MockObject */
    private $taxManager;

    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var SkipOrderTaxRecalculationResolver */
    private $resolver;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->taxManager = $this->createMock(TaxManager::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->resolver = new SkipOrderTaxRecalculationResolver(
            $this->doctrine,
            $this->taxManager,
            $this->frontendHelper,
            $this->eventDispatcher
        );
    }

    /**
     * @dataProvider resolveWhenNoIdentifierOrClassNameDataProvider
     */
    public function testResolveWhenNoIdentifierOrClassName(Taxable $taxable): void
    {
        $this->doctrine
            ->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve($taxable);
    }

    public function resolveWhenNoIdentifierOrClassNameDataProvider(): array
    {
        return [
            [(new Taxable())],
            [(new Taxable())->setIdentifier(42)],
            [(new Taxable())->setClassName(\stdClass::class)],
        ];
    }

    public function testResolveWhenFrontend(): void
    {
        $taxable = (new Taxable())
            ->setIdentifier(42)
            ->setClassName(\stdClass::class);

        $this->frontendHelper
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->doctrine
            ->expects($this->never())
            ->method($this->anything());

        $this->resolver->resolve($taxable);
    }

    public function testResolveWhenNoEntityManager(): void
    {
        $taxable = (new Taxable())
            ->setIdentifier(42)
            ->setClassName(\stdClass::class);

        $this->frontendHelper
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);

        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(\stdClass::class)
            ->willReturn(null);

        $this->resolver->resolve($taxable);
    }
}
