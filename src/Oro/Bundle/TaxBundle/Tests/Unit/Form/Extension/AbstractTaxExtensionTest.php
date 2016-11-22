<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Form\Extension\AbstractTaxExtension;

abstract class AbstractTaxExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityRepository;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturnCallback(
                function ($entity) {
                    /** @var Account|Product $entity */
                    return $entity->getId();
                }
            );
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper);
    }

    /**
     * @return AbstractTaxExtension
     */
    abstract protected function getExtension();

    /**
     * @param bool $expectsManager
     * @param bool $expectsRepository
     */
    abstract protected function prepareDoctrineHelper($expectsManager = false, $expectsRepository = false);

    public function testOnPostSetDataNoEntity()
    {
        $this->prepareDoctrineHelper();

        $event = $this->createEvent(null);

        $this->getExtension()->onPostSetData($event);
    }

    public function testOnPostSetDataNewEntity()
    {
        $this->prepareDoctrineHelper();

        $event = $this->createEvent($this->createTaxCodeTarget());

        $this->getExtension()->onPostSetData($event);
    }

    /**
     * @param mixed $data
     *
     * @return FormEvent
     */
    protected function createEvent($data)
    {
        $taxCodeForm = $this->getMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('taxCode')
            ->willReturn($taxCodeForm);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param int|null $id
     *
     * @return object
     */
    abstract protected function createTaxCodeTarget($id = null);

    /**
     * @param int|null $id
     *
     * @return AbstractTaxCode
     */
    abstract protected function createTaxCode($id = null);


    /**
     * @param FormEvent $event
     * @param AbstractTaxCode $taxCode
     */
    protected function assertTaxCodeAdd(FormEvent $event, AbstractTaxCode $taxCode)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($taxCode));
    }

    public function testOnPostSubmitNoEntity()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->never())
            ->method('isValid');

        $this->getExtension()->onPostSubmit($event);
    }


    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createTaxCodeTarget());
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->never())
            ->method('getData');

        $this->getExtension()->onPostSubmit($event);
    }
}
