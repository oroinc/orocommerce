<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\CheckoutBundle\Action\DefaultCheckoutOwnerSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\User;

class DefaultCheckoutOwnerSetterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

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
        $this->configManager              = $this->createMock(ConfigManager::class);
        $this->doctrineHelper             = $this->createMock(DoctrineHelper::class);
        $this->defaultCheckoutOwnerSetter = new DefaultCheckoutOwnerSetter(
            $this->configManager,
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

    public function testSetDefaultOwnerWithNullSettings()
    {
        $repository  = $this->createMock(EntityRepository::class);
        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER
        );
        $this->configManager->expects($this->once())
            ->method('get')
            ->with($settingsKey)
            ->will($this->returnValue(null));

        $owner = new User();
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

    public function testSetDefaultOwnerWithNotExistOwnerId()
    {
        $repository  = $this->createMock(EntityRepository::class);
        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER
        );
        $ownerId = 100500;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with($settingsKey)
            ->will($this->returnValue($ownerId));

        $repository->expects($this->once())
            ->method('find')
            ->with($ownerId)
            ->will($this->returnValue(null));
        $owner = new User();
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

    public function testSetDefaultOwnerWithExistOwnerId()
    {
        $repository = $this->createMock(EntityRepository::class);
        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER
        );
        $ownerId = 100500;
        $this->configManager->expects($this->once())
            ->method('get')
            ->with($settingsKey)
            ->will($this->returnValue($ownerId));

        $owner = new User();
        $repository->expects($this->once())
            ->method('find')
            ->with($ownerId)
            ->will($this->returnValue($owner));
        $repository->expects($this->never())
            ->method('findOneBy')
            ->with([]);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(User::class)
            ->will($this->returnValue($repository));

        $checkout = new Checkout();
        $this->defaultCheckoutOwnerSetter->setDefaultOwner($checkout);

        $this->assertEquals($owner, $checkout->getOwner());
    }
}
