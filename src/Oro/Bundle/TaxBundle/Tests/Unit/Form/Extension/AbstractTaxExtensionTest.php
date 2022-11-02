<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\TaxBundle\Entity\AbstractTaxCode;
use Oro\Bundle\TaxBundle\Form\Extension\AbstractTaxExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

abstract class AbstractTaxExtensionTest extends \PHPUnit\Framework\TestCase
{
    abstract protected function getExtension(): AbstractTaxExtension;

    abstract protected function createTaxCodeTarget(int $id = null): object;

    abstract protected function createTaxCode(int $id = null): AbstractTaxCode;

    protected function createEvent(mixed $data): FormEvent
    {
        $taxCodeForm = $this->createMock(FormInterface::class);

        $mainForm = $this->createMock(FormInterface::class);
        $mainForm->expects($this->any())
            ->method('get')
            ->with('taxCode')
            ->willReturn($taxCodeForm);

        return new FormEvent($mainForm, $data);
    }

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
            ->willReturn($taxCode);
    }

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
