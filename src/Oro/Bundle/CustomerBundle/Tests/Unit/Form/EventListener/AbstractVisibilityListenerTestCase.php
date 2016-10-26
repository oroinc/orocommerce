<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Form\EventListener\AbstractVisibilityListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractVisibilityListenerTestCase extends \PHPUnit_Framework_TestCase
{
    const CATEGORY_VISIBILITY_CLASS = 'Oro\Bundle\CustomerBundle\Entity\Visibility\CategoryVisibility';
    const ACCOUNT_CATEGORY_VISIBILITY_CLASS = 'Oro\Bundle\CustomerBundle\Entity\Visibility\AccountCategoryVisibility';
    const ACCOUNT_GROUP_CATEGORY_VISIBILITY_CLASS =
        'Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupCategoryVisibility';

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var AbstractVisibilityListener */
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

    /** @var  Website */
    protected $website;

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
        $this->website = new Website();
        $formConfig->expects($this->any())->method('getOption')
            ->willReturnMap(
                [
                    ['targetEntityField', null, 'category'],
                    ['website', null, $this->website],
                    ['allClass', null, self::CATEGORY_VISIBILITY_CLASS],
                    ['accountClass', null, self::ACCOUNT_CATEGORY_VISIBILITY_CLASS],
                    ['accountGroupClass', null, self::ACCOUNT_GROUP_CATEGORY_VISIBILITY_CLASS],
                ]
            );

        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->form->expects($this->any())->method('getConfig')->willReturn($formConfig);
    }

    /** @return AbstractVisibilityListener */
    abstract public function getListener();

    /**
     * @param array $criteria
     * @return array
     */
    protected function addWebsiteCriteria(array $criteria)
    {
        if ($this->website) {
            $criteria['website'] = $this->website;
        }

        return $criteria;
    }
}
