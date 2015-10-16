<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\AccountBundle\EventListener\VisibilityAbstractListener;
use Symfony\Component\Form\FormInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

abstract class VisibilityAbstractListenerTestCase extends \PHPUnit_Framework_TestCase
{
    const CATEGORY_VISIBILITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility';
    const ACCOUNT_CATEGORY_VISIBILITY_CLASS = 'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_CLASS =
        'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var VisibilityAbstractListener */
    protected $listener;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryVisibilityRepository;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $accountCategoryVisibilityRepository;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
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
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->accountGroupCategoryVisibilityRepository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
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

        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formConfig->expects($this->any())->method('getOption')
            ->willReturnMap(
                [
                    ['targetEntityField', null, 'category'],
                    ['allClass', null, self::CATEGORY_VISIBILITY_CLASS],
                    ['accountClass', null, self::ACCOUNT_CATEGORY_VISIBILITY_CLASS],
                    ['accountGroupClass', null, self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_CLASS],
                ]
            );

        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->form->expects($this->any())->method('getConfig')->willReturn($formConfig);
    }

    /** @return VisibilityAbstractListener */
    abstract public function getListener();
}
