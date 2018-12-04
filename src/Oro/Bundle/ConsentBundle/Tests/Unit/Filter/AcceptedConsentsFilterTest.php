<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConsentBundle\Filter\AcceptedConsentsFilter;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\Consent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;

class AcceptedConsentsFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AcceptedConsentsFilter
     */
    private $filter;

    /**
     * @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $factoryMock;

    /**
     * @var FilterUtility
     */
    private $filterUtility;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->factoryMock = $this->createMock(FormFactoryInterface::class);
        $this->filterUtility = new FilterUtility();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new AcceptedConsentsFilter(
            $this->factoryMock,
            $this->filterUtility,
            $this->doctrineHelper
        );
    }

    public function testGetMetadataConsents()
    {
        $formView = new FormView();
        $formMock = $this->createMock(FormInterface::class);
        $this->factoryMock->expects($this->once())
            ->method('create')
            ->willReturn($formMock);

        $formMock->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $typeView = new FormView();
        $formView->children['type'] = $typeView;
        $typeView->vars['choices'] = ['some_choices'];
        $this->filter->init('FooName', ['params']);

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('Oro\Bundle\ConsentBundle\Entity\Consent')
            ->willReturn($repository);

        $consent = (new Consent())->setDefaultName('Default name');

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
           'class' => "",
           'select2ConfigData' => [[
               'id' => null,
               'value' => null,
               'text' => 'Default name'
           ]]
        ];
        $this->assertSame($expectedMetadata, $this->filter->getMetadata());
    }
}
