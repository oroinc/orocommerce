<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Placeholder;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Placeholder\CPLIdPlaceholder;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CPLIdPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CPLIdPlaceholder
     */
    private $placeholder;

    /**
     * @var PriceListTreeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListTreeHandler;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    protected function setUp()
    {
        $this->priceListTreeHandler = $this->getMockBuilder(PriceListTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->placeholder = new CPLIdPlaceholder($this->priceListTreeHandler, $this->tokenStorage);
    }

    public function testGetPlaceholder()
    {
        $this->assertSame(CPLIdPlaceholder::NAME, $this->placeholder->getPlaceholder());
    }

    public function testReplaceValue()
    {
        $this->assertSame("test_1", $this->placeholder->replace("test_CPL_ID", ["CPL_ID" => 1]));
    }

    public function testReplaceDefault()
    {
        $user = new CustomerUser();
        $account = new Account();
        $user->setAccount($account);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($account)
            ->willReturn($this->getEntity(CombinedPriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_CPL_ID"));
    }

    public function testReplaceDefaultUserNotAuthenticated()
    {
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
        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with(null)
            ->willReturn($this->getEntity(CombinedPriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_CPL_ID"));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Can't get current cpl
     */
    public function testReplaceDefaultCplNotFound()
    {
        $user = new CustomerUser();
        $account = new Account();
        $user->setAccount($account);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($account)
            ->willReturn(null);

        $this->placeholder->replaceDefault("test_CPL_ID");
    }
}
