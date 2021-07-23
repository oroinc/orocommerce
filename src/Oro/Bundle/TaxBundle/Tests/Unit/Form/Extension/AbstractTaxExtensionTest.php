<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Form\Extension\AbstractTaxExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

abstract class AbstractTaxExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityRepository;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturnCallback(
                function ($entity) {
                    /** @var Customer|Product $entity */
                    return $entity->getId();
                }
            );
    }

    protected function tearDown(): void
    {
        unset($this->doctrineHelper);
    }

    /**
     * @return AbstractTaxExtension
     */
    abstract protected function getExtension();

    public function testOnPostSetDataNoEntity()
    {
        $event = $this->createEvent(null);

        $this->getExtension()->onPostSetData($event);
    }

    public function testOnPostSetDataNewEntity()
    {
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
        $taxCodeForm = $this->createMock('Symfony\Component\Form\FormInterface');

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $mainForm->expects($this->any())
            ->method('get')
            ->with('taxCode')
            ->willReturn($taxCodeForm);

        return new FormEvent($mainForm, $data);
    }

    /**
     * @param int|null $id
     *
     * @return object|\PHPUnit\Framework\MockObject\MockObject
     */
    abstract protected function createTaxCodeTarget($id = null);

    /**
     * @param int|null $id
     *
     * @return AbstractTaxCode
     */
    abstract protected function createTaxCode($id = null);

    protected function assertTaxCodeAdd(FormEvent $event, AbstractTaxCode $taxCode)
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($taxCode));
    }

    public function testOnPostSubmitNoEntity()
    {
        $event = $this->createEvent(null);
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->never())
            ->method('isValid');

        $this->getExtension()->onPostSubmit($event);
    }

    public function testOnPostSubmitInvalidForm()
    {
        $event = $this->createEvent($this->createTaxCodeTarget());
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $mainForm */
        $mainForm = $event->getForm();
        $mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $taxCodeForm */
        $taxCodeForm = $mainForm->get('taxCode');
        $taxCodeForm->expects($this->never())
            ->method('getData');

        $this->getExtension()->onPostSubmit($event);
    }
}
