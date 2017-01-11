<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Model\RequestManager;

class RequestManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RequestManager
     */
    protected $requestManager;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestManager = new RequestManager($this->securityFacade, $this->doctrineHelper);
    }

    public function testCreate()
    {
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($customerUser);
        $expected = new Request();
        $expected->setCustomerUser($customerUser);
        $expected->setCustomer($customer);

        $actual = $this->requestManager->create();
        $this->assertInstanceOf(Request::class, $actual);
        $this->assertEquals($expected->getCustomer(), $actual->getCustomer());
        $this->assertEquals($expected->getCustomerUser(), $actual->getCustomerUser());
        $this->assertEquals($expected->getCreatedAt(), $actual->getCreatedAt(), '', 5);
        $this->assertEquals($expected->getUpdatedAt(), $actual->getUpdatedAt(), '', 5);
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
