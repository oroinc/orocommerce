<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Form\EventListener\AbstractCategoryListener;

abstract class AbstractCategoryListenerTestCase extends \PHPUnit_Framework_TestCase
{
    const CATEGORY_VISIBILITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility';
    const ACCOUNT_CATEGORY_VISIBILITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_CLASS =
        'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var AbstractCategoryListener */
    protected $listener;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryVisibilityRepository;

    /** @var AccountCategoryVisibilityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $accountCategoryVisibilityRepository;

    /** @var AccountGroupCategoryVisibilityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $accountGroupCategoryVisibilityRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $em;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->categoryVisibilityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountCategoryVisibilityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountCategoryVisibilityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountGroupCategoryVisibilityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupCategoryVisibilityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->em->expects($this->any())->method('getRepository')->will(
            $this->returnValueMap(
                [
                    [self::CATEGORY_VISIBILITY_CLASS, $this->categoryVisibilityRepository],
                    [self::ACCOUNT_CATEGORY_VISIBILITY_CLASS, $this->accountCategoryVisibilityRepository],
                    [self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_CLASS, $this->accountGroupCategoryVisibilityRepository],
                ]
            )
        );

        $this->registry->expects($this->any())->method('getManagerForClass')->willReturn($this->em);

        $this->listener = $this->getListener();

        $this->listener->setCategoryVisibilityClass(self::CATEGORY_VISIBILITY_CLASS);
        $this->listener->setAccountCategoryVisibilityClass(self::ACCOUNT_CATEGORY_VISIBILITY_CLASS);
        $this->listener->setAccountGroupCategoryVisibilityClass(self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_CLASS);
    }

    /** @return AbstractCategoryListener */
    abstract public function getListener();
}
