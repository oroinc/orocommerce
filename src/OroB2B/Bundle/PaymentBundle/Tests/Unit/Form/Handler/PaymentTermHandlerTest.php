<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Form\Handler\PaymentTermHandler;

class PaymentTermHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var PaymentTermHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var PaymentTerm
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

        $this->entity = new PaymentTerm();
        $this->handler = new PaymentTermHandler($this->form, $this->request, $this->manager);
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

        $this->form->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap(
                [
                    ['appendAccounts', $this->getEntityForm($appendedAccount)],
                    ['removeAccounts', $this->getEntityForm($removedAccount)],
                    ['appendAccountGroups', $this->getEntityForm($appendedAccountGroup)],
                    ['removeAccountGroups', $this->getEntityForm($removedAccountGroup)]
                ]
            );

        $this->prepareServices();

        $this->assertTrue($this->handler->process($this->entity));

        $this->assertFalse($this->entity->getAccounts()->contains($removedAccount));
        $this->assertFalse($this->entity->getAccountGroups()->contains($removedAccountGroup));

        $this->assertTrue($this->entity->getAccounts()->contains($appendedAccount));
        $this->assertTrue($this->entity->getAccountGroups()->contains($appendedAccountGroup));
    }

    protected function prepareServices()
    {
        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once())->method('submit')->with($this->request);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->manager->expects($this->at(0))->method('persist')->with($this->isType('object'));

        $repository = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository')
            ->disableOriginalConstructor()->getMock();

        $repository->expects($this->any())->method('setPaymentTermToAccount')->will(
            $this->returnCallback(
                function (Account $account, PaymentTerm $paymentTerm = null) {
                    $this->entity->removeAccount($account);
                    if ($paymentTerm) {
                        $this->entity->addAccount($account);
                    }
                }
            )
        );

        $repository->expects($this->any())->method('setPaymentTermToAccountGroup')->will(
            $this->returnCallback(
                function (AccountGroup $accountGroup, PaymentTerm $paymentTerm = null) {
                    $this->entity->removeAccountGroup($accountGroup);
                    if ($paymentTerm) {
                        $this->entity->addAccountGroup($accountGroup);
                    }
                }
            )
        );

        $this->manager->expects($this->any())->method('getRepository')
            ->with('OroB2BPaymentBundle:PaymentTerm')
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
    protected function getEntityForm($entity)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('getData')
            ->willReturn([$entity]);

        return $form;
    }

    public function testWorseMethod()
    {
        $this->request->setMethod('GET');
        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testInvalidProcess()
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
