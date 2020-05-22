<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Model\RequestManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;

class RequestManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var RequestManager
     */
    protected $requestManager;

    /**
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenAccessor;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var GuestCustomerUserManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $guestCustomerUserManager;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteManager;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->guestCustomerUserManager = $this->createMock(GuestCustomerUserManager::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->requestManager = new RequestManager(
            $this->tokenAccessor,
            $this->doctrineHelper,
            $this->guestCustomerUserManager,
            $this->websiteManager
        );
    }

    public function testCreate()
    {
        $website = new Website();
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $expected = new Request();
        $expected->setCustomerUser($customerUser);
        $expected->setCustomer($customer);
        $expected->setWebsite($website);

        $actual = $this->requestManager->create();
        $this->assertInstanceOf(Request::class, $actual);
        $this->assertEquals($expected->getWebsite(), $actual->getWebsite());
        $this->assertEquals($expected->getCustomer(), $actual->getCustomer());
        $this->assertEquals($expected->getCustomerUser(), $actual->getCustomerUser());
        $this->assertEqualsWithDelta($expected->getCreatedAt(), $actual->getCreatedAt(), 5);
        $this->assertEqualsWithDelta($expected->getUpdatedAt(), $actual->getUpdatedAt(), 5);
    }

    public function testAddProductItemToRequest()
    {
        $productId = 1;
        $unitCode = 'bottle';
        $quantity = 10;
        $data = [
            $productId => [[
                'unit' => $unitCode,
                'quantity' => $quantity,
            ]],
        ];
        $request = new Request();

        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        $unit = $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => $unitCode]);

        $productReposiotry = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->at(0))
            ->method('getEntityRepositoryForClass')
            ->willReturn($productReposiotry);
        $productReposiotry->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$productId]])
            ->willReturn([$product]);

        $unitRepository = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->at(1))
            ->method('getEntityRepositoryForClass')
            ->willReturn($unitRepository);
        $unitRepository->expects($this->once())
            ->method('getProductsUnitsByCodes')
            ->with([$productId], [$unitCode])
            ->willReturn([$unitCode => $unit]);
        $this->assertEmpty($request->getRequestProducts());

        $this->requestManager->addProductLineItemsToRequest($request, $data);

        $this->assertNotEmpty($request->getRequestProducts());
    }
}
