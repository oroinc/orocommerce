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
        $request = new Request();
        $productId = 1;
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => $productId]);
        $unitCode = 'bottle';
        $unit = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => $unitCode]);
        $quantity = 10;

        $repo = $this->getMock('\Doctrine\Common\Persistence\ObjectRepository');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repo);
        $repo->expects($this->once())
            ->method('find')
            ->willReturn($product);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($unit);
        $this->assertEmpty($request->getRequestProducts());
        $this->requestManager->addProductItemToRequest($request, $productId, $unit, $quantity);

        $this->assertNotEmpty($request->getRequestProducts());
    }
}
