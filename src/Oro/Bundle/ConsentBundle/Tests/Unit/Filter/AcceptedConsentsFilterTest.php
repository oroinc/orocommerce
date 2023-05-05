<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Filter\AcceptedConsentsFilter;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent as ConsentStub;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class AcceptedConsentsFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AcceptedConsentsFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filter = new AcceptedConsentsFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine
        );
    }

    public function testGetMetadataConsents()
    {
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects($this->once())
            ->method('create')
            ->willReturn($form);

        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $typeView = new FormView();
        $formView->children['type'] = $typeView;
        $typeView->vars['choices'] = ['some_choices'];
        $this->filter->init('FooName', ['params']);

        $repository = $this->createMock(EntityRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(Consent::class)
            ->willReturn($repository);

        $consent = (new ConsentStub())->setDefaultName('Default name');

        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([$consent]);

        $expectedMetadata = [
           'name' => 'FooName',
           'label' => 'FooName',
           'choices' => ['some_choices'],
            0 => 'params',
           'type' => 'dictionary',
           'lazy' => false,
           'class' => '',
           'select2ConfigData' => [[
               'id' => null,
               'value' => null,
               'text' => 'Default name'
           ]]
        ];
        $this->assertSame($expectedMetadata, $this->filter->getMetadata());
    }
}
