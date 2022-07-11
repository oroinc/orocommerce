<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\AbstractPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Placeholder\PriceListIdPlaceholder;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PriceListIdPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var PriceListIdPlaceholder
     */
    private $placeholder;

    /**
     * @var AbstractPriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceListTreeHandler;

    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var CustomerUserRelationsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerUserRelationsProvider;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->priceListTreeHandler = $this->createMock(AbstractPriceListTreeHandler::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->placeholder = new PriceListIdPlaceholder(
            $this->priceListTreeHandler,
            $this->tokenStorage,
            $this->configManager,
            $this->customerUserRelationsProvider
        );
        $this->placeholder->setFeatureChecker($this->featureChecker);
        $this->placeholder->addFeature('oro_price_lists_flat');
    }

    public function testGetPlaceholder()
    {
        $this->assertSame(PriceListIdPlaceholder::NAME, $this->placeholder->getPlaceholder());
    }

    public function testReplaceValue()
    {
        $this->assertSame("test_1", $this->placeholder->replace("test_PRICE_LIST_ID", ["PRICE_LIST_ID" => 1]));
    }

    public function testReplaceDefaultNoPlaceholderInString()
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $this->tokenStorage->expects($this->never())
            ->method($this->anything());

        $this->priceListTreeHandler->expects($this->never())
            ->method($this->anything());

        $this->assertSame('test_LOCALE_ID', $this->placeholder->replaceDefault("test_LOCALE_ID"));
    }

    public function testReplaceDefaultFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(false);

        $this->tokenStorage->expects($this->never())
            ->method($this->anything());

        $this->priceListTreeHandler->expects($this->never())
            ->method($this->anything());

        $this->assertSame('test_', $this->placeholder->replaceDefault("test_PRICE_LIST_ID"));
    }

    public function testReplaceDefaultCustomerGroupAccuracy()
    {
        $user = new CustomerUser();
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $customer->setGroup($customerGroup);
        $user->setCustomer($customer);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer_group');

        $expectedCustomer = new Customer();
        $expectedCustomer->setGroup($customerGroup);
        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($expectedCustomer)
            ->willReturn($this->getEntity(PriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_PRICE_LIST_ID"));
    }

    public function testReplaceDefaultCustomerAccuracy()
    {
        $user = new CustomerUser();
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 2]);
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 1]);
        $customer->setGroup($customerGroup);
        $user->setCustomer($customer);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer');

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer)
            ->willReturn($this->getEntity(PriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_PRICE_LIST_ID"));
    }

    public function testReplaceDefaultUserNotAuthenticated()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer');

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with(null)
            ->willReturn($this->getEntity(PriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_PRICE_LIST_ID"));
    }

    public function testReplaceDefaultNoToken()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer');

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with(null)
            ->willReturn($this->getEntity(PriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_PRICE_LIST_ID"));
    }

    public function testReplaceDefaultAnonymousToken()
    {
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 5]);
        $customer = $this->getEntity(Customer::class, ['group' => $customerGroup]);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('customer');

        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token
            ->expects($this->once())
            ->method('getUser');

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->customerUserRelationsProvider
            ->expects($this->once())
            ->method('getCustomerIncludingEmpty')
            ->with(null)
            ->willReturn($customer);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer)
            ->willReturn($this->getEntity(PriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_PRICE_LIST_ID"));
    }

    public function testReplaceDefaultWesiteAccuracy()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_flat')
            ->willReturn(true);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn('website');

        $this->tokenStorage->expects($this->never())
            ->method($this->anything());

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with(null)
            ->willReturn($this->getEntity(PriceList::class, ['id' => 1]));

        $this->assertSame("test_1", $this->placeholder->replaceDefault("test_PRICE_LIST_ID"));
    }
}
