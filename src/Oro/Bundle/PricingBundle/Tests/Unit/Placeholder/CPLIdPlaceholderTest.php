<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Placeholder\CPLIdPlaceholder;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CPLIdPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CPLIdPlaceholder
     */
    private $placeholder;

    /**
     * @var CombinedPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceListTreeHandler;

    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->placeholder = new CPLIdPlaceholder($this->priceListTreeHandler, $this->tokenStorage);
        $this->placeholder->setFeatureChecker($this->featureChecker);
        $this->placeholder->addFeature('oro_price_lists_combined');
    }

    public function testGetPlaceholder()
    {
        $this->assertSame(CPLIdPlaceholder::NAME, $this->placeholder->getPlaceholder());
    }

    public function testReplaceValue()
    {
        $this->assertSame("test_1", $this->placeholder->replace("test_CPL_ID", ["CPL_ID" => 1]));
    }

    public function testReplaceDefaultFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);

        $this->tokenStorage->expects($this->never())
            ->method($this->anything());

        $this->priceListTreeHandler->expects($this->never())
            ->method($this->anything());

        $this->assertSame('test_', $this->placeholder->replaceDefault("test_CPL_ID"));
    }

    public function testReplaceDefault()
    {
        $user = new CustomerUser();
        $customer = new Customer();
        $user->setCustomer($customer);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer)
            ->willReturn($this->getEntity(CombinedPriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_CPL_ID"));
    }

    public function testReplaceDefaultUserNotAuthenticated()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with(null)
            ->willReturn($this->getEntity(CombinedPriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_CPL_ID"));
    }

    public function testReplaceDefaultNoToken()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with(null)
            ->willReturn($this->getEntity(CombinedPriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_CPL_ID"));
    }

    public function testReplaceDefaultCplNotFound()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Can't get current cpl");

        $user = new CustomerUser();
        $customer = new Customer();
        $user->setCustomer($customer);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer)
            ->willReturn(null);

        $this->placeholder->replaceDefault("test_CPL_ID");
    }
}
