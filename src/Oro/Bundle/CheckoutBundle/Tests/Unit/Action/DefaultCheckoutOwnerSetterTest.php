<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CheckoutBundle\Action\DefaultCheckoutOwnerSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultCheckoutOwnerSetterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var DefaultCheckoutOwnerSetter
     */
    private $defaultCheckoutOwnerSetter;

    protected function setUp()
    {
        $this->doctrineHelper             = $this->createMock(DoctrineHelper::class);
        $this->defaultCheckoutOwnerSetter = new DefaultCheckoutOwnerSetter(
            $this->doctrineHelper
        );

        parent::setUp();
    }

    public function testSetDefaultOwnerAlreadySet()
    {
        $checkout = new Checkout();
        $owner    = new User();
        $checkout->setOwner($owner);

        $this->defaultCheckoutOwnerSetter->setDefaultOwner($checkout);
        $this->assertEquals($owner, $checkout->getOwner());
    }

    public function testSetDefaultOwner()
    {
        $repository = $this->createMock(EntityRepository::class);
        $owner      = new User();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with([])
            ->will($this->returnValue($owner));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(User::class)
            ->will($this->returnValue($repository));


        $checkout = new Checkout();
        $this->defaultCheckoutOwnerSetter->setDefaultOwner($checkout);

        $this->assertEquals($owner, $checkout->getOwner());
    }
}
