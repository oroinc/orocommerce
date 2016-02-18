<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Duplicator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPBundle\Duplicator\RequestDuplicator;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestDuplicatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestDuplicator
     */
    protected $duplicator;

    public function setUp()
    {
        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $statusOpen = $this->getMock('OroB2B\Bundle\RFPBundle\Entity\RequestStatus');
        $statusOpen->expects($this->any())->method('getName')->willReturn(RequestStatus::OPEN);
        $statusRepository = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $statusRepository->expects($this->any())->method('findOneBy')->willReturn($statusOpen);

        $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $manager->expects($this->exactly(2))->method('getConnection')->willReturn($connection);
        $manager->expects($this->once())->method('persist');
        $manager->expects($this->once())->method('flush');
        $doctrineHelper->expects($this->any())->method('getEntityRepository')->willReturnMap([
            ['OroB2BRFPBundle:RequestStatus', $statusRepository],
        ]);

        $doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($manager);
        $this->duplicator = new RequestDuplicator($doctrineHelper);
    }

    /**
     * @throws \Exception
     */
    public function testDuplicate()
    {
        $request = $this->getRFP();
        $requestCopy = $this->duplicator->duplicate($request);
        $requestCopy->setCreatedAt($request->getCreatedAt());
        $requestCopy->setUpdatedAt($request->getUpdatedAt());
        $this->checkCopy($request, $requestCopy);
    }

    public function testDuplicateExclude()
    {
        $request = $this->getRFP();
        $reflect = new \ReflectionClass($request);
        $fields = $reflect->getProperties(
            \ReflectionProperty::IS_PUBLIC
            | \ReflectionProperty::IS_PROTECTED
            | \ReflectionProperty::IS_PRIVATE);

        $excludeFields = array_map(function (\ReflectionProperty $field) {
            return $field->getName();
        }, $fields);
        $requestCopy = $this->duplicator->duplicate($request, $excludeFields);

        foreach ($excludeFields as $field) {
            $method = 'get' . $field;
            $value = $requestCopy->$method();
            if ($field === 'status') {
                /** @var $value RequestStatus */
                $this->assertEquals(RequestStatus::OPEN, $value->getName());
            } else {
                $this->assertEmpty($value);
            }
        }
    }

    /**
     * @param Request $request
     * @param Request $requestCopy
     */
    protected function checkCopy(Request $request, Request $requestCopy)
    {
        $this->assertNotSame($request, $requestCopy);
        $this->assertEquals($request, $requestCopy);

        $copiesUsers = $requestCopy->getAssignedUsers();
        foreach ($request->getAssignedUsers() as $key => $user) {
            $this->assertSame($copiesUsers[$key], $user);
        }

        $copiesAccountUsers = $requestCopy->getAssignedAccountUsers();
        foreach ($request->getAssignedAccountUsers() as $key => $accountUser) {
            $this->assertSame($copiesAccountUsers[$key], $accountUser);
        }

        $copiesProducts = $requestCopy->getRequestProducts();
        foreach ($request->getRequestProducts() as $key => $requestProduct) {
            $this->assertNotSame($copiesProducts[$key], $requestProduct);
            $this->assertEquals($copiesProducts[$key], $requestProduct);
        }

    }

    /**
     * @return Request
     */
    protected function getRFP()
    {
        $request = new Request();
        /** @var Account $account */
        $account = new Account();
        $request->setAccount($account);

        /** @var AccountUser $accountUser */
        $accountUser = new AccountUser();
        $request->setAccountUser($accountUser);
        $request->setStatus($this->getMock('OroB2B\Bundle\RFPBundle\Entity\RequestStatus'));
        $request->setCompany('test company');

        $request->setEmail('test@test.com');

        $request->setFirstName('Jone');
        $request->setLastName('Dou');

        $request->addAssignedAccountUser($accountUser);

        /** @var User $user */
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $request->addAssignedUser($user);

        $requestProduct = new RequestProduct();
        $requestProduct->setComment('Product comment');

        $requestProduct->setProduct(new Product());
        $requestProduct->setProductSku('SKU');
        $requestProduct->addRequestProductItem(new RequestProductItem());
        $request->addRequestProduct($requestProduct);

        return $request;
    }
}
