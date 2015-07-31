<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Handler\PriceListHandler;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var PriceListHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var PriceList
     */
    protected $entity;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request();

        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = new PriceList();
        $this->handler = new PriceListHandler($this->form, $this->request, $this->manager);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->manager, $this->request, $this->form, $this->entity, $this->handler);
    }

    public function testProcessValidData()
    {
        /** @var Account $appendedAccount */
        $appendedAccount = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 1);
        /** @var Account $removedAccount */
        $removedAccount = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', 2);
        $this->entity->addAccount($removedAccount);

        /** @var AccountGroup $appendedAccountGroup */
        $appendedAccountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 1);
        /** @var AccountGroup $removedAccountGroup */
        $removedAccountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', 2);
        $this->entity->addAccountGroup($removedAccountGroup);

        /** @var Website $appendedWebsite */
        $appendedWebsite = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 1);
        /** @var Website $removedWebsite */
        $removedWebsite = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', 2);
        $this->entity->addWebsite($removedWebsite);

        $this->form->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap(
                [
                    ['appendAccounts', $this->getFormForEntity($appendedAccount)],
                    ['removeAccounts', $this->getFormForEntity($removedAccount)],
                    ['appendAccountGroups', $this->getFormForEntity($appendedAccountGroup)],
                    ['removeAccountGroups', $this->getFormForEntity($removedAccountGroup)],
                    ['appendWebsites', $this->getFormForEntity($appendedWebsite)],
                    ['removeWebsites', $this->getFormForEntity($removedWebsite)],
                ]
            );

        $this->prepareServices();

        $this->assertTrue($this->handler->process($this->entity));

        $this->assertFalse($this->entity->getAccounts()->contains($removedAccount));
        $this->assertFalse($this->entity->getAccountGroups()->contains($removedAccountGroup));
        $this->assertFalse($this->entity->getWebsites()->contains($removedWebsite));

        $this->assertTrue($this->entity->getAccounts()->contains($appendedAccount));
        $this->assertTrue($this->entity->getAccountGroups()->contains($appendedAccountGroup));
        $this->assertTrue($this->entity->getWebsites()->contains($appendedWebsite));
    }

    protected function prepareServices()
    {
        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once())->method('submit')->with($this->request);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->manager->expects($this->at(0))->method('persist')->with($this->isType('object'));

        $repository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()->getMock();

        $repository->expects($this->any())->method('setPriceListToAccount')->will(
            $this->returnCallback(
                function (Account $account, PriceList $priceList = null) {
                    $this->entity->removeAccount($account);
                    if ($priceList) {
                        $this->entity->addAccount($account);
                    }
                }
            )
        );

        $repository->expects($this->any())->method('setPriceListToAccountGroup')->will(
            $this->returnCallback(
                function (AccountGroup $accountGroup, PriceList $priceList = null) {
                    $this->entity->removeAccountGroup($accountGroup);
                    if ($priceList) {
                        $this->entity->addAccountGroup($accountGroup);
                    }
                }
            )
        );

        $repository->expects($this->any())->method('setPriceListToWebsite')->will(
            $this->returnCallback(
                function (Website $website, PriceList $priceList = null) {
                    $this->entity->removeWebsite($website);
                    if ($priceList) {
                        $this->entity->addWebsite($website);
                    }
                }
            )
        );

        $this->manager->expects($this->any())->method('getRepository')->with($this->isType('string'))
            ->willReturn($repository);

        $this->manager->expects($this->exactly(2))->method('flush');
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }

    /**
     * @param object $entity
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Form\Form
     */
    protected function getFormForEntity($entity)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('getData')
            ->willReturn([$entity]);

        return $form;
    }

    public function testBadMethod()
    {
        $this->request->setMethod('GET');
        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessInvalid()
    {
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $this->assertFalse($this->handler->process($this->entity));
    }
}
