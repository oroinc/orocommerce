<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Model\RequestManager;

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
        $account = new Account();
        $accountUser = new AccountUser();
        $accountUser->setAccount($account);
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);
        $expected = new Request();
        $expected->setAccountUser($accountUser);
        $expected->setAccount($account);

        $actual = $this->requestManager->create();
        $this->assertEquals($expected, $actual);
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
        
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        $unit = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => $unitCode]);

        $productReposiotry = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->at(0))
            ->method('getEntityRepositoryForClass')
            ->willReturn($productReposiotry);
        $productReposiotry->expects($this->once())
            ->method('findBy')
            ->with(['id' => [$productId]])
            ->willReturn([$product]);

        $unitRepository = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
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
