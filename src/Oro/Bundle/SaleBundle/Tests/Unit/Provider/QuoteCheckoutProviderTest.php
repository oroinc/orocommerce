<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteDemandRepository;
use Oro\Bundle\SaleBundle\Provider\QuoteCheckoutProvider;

class QuoteCheckoutProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var QuoteDemandRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteDemandRepository;

    /** @var CheckoutRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutRepository;

    /** @var QuoteCheckoutProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->quoteDemandRepository = $this->createMock(QuoteDemandRepository::class);
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->provider = new QuoteCheckoutProvider($this->managerRegistry);
    }

    /**
     * @dataProvider quoteDemandDataProvider
     */
    public function testGetCheckoutByQuote(QuoteDemand $quoteDemand = null)
    {
        $quote = $this->createMock(Quote::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $workflowName = 'test_workflow';

        $this->managerRegistry->expects($this->exactly($quoteDemand ? 2 : 1))
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->quoteDemandRepository->expects($this->once())
            ->method('getQuoteDemandByQuote')
            ->with($quote, $customerUser)
            ->willReturn($quoteDemand);

        $this->checkoutRepository->expects($this->exactly($quoteDemand ? 1 : 0))
            ->method('findCheckoutByCustomerUserAndSourceCriteriaWithCurrency')
            ->with($customerUser, ['quoteDemand' => $quoteDemand], $workflowName);

        $this->entityManager->expects($this->exactly($quoteDemand ? 2 : 1))
            ->method('getRepository')
            ->willReturnMap([
                [QuoteDemand::class,$this->quoteDemandRepository],
                [Checkout::class,$this->checkoutRepository],
            ]);

        $this->provider->getCheckoutByQuote($quote, $customerUser, $workflowName);
    }

    public function quoteDemandDataProvider(): array
    {
        return [
            'quote demand' => [
                'quoteDemand' => $this->createMock(QuoteDemand::class),
            ],
            'no quote demand' => [
                'quoteDemand' => null,
            ],
        ];
    }
}
