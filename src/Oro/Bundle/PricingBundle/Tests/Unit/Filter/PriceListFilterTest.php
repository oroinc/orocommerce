<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Filter\PriceListFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PriceListFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceListFilter */
    private $priceListFilter;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->priceListFilter = new PriceListFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine
        );
    }

    /**
     * @dataProvider getMetadataDataProvider
     */
    public function testGetMetadata(array $formViewVars, array $expectedMetadata)
    {
        $formView = new FormView();
        $valueFormView = new FormView();
        $typeFormView = new FormView();
        $formView->children = ['value' => $valueFormView, 'type' => $typeFormView];
        $formView->vars = $formViewVars;
        $typeFormView->vars = ['choices' => []];
        $valueFormView->vars = [
            'multiple' => false,
            'choices' => [],
        ];

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('create')
            ->willReturn($form);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $metadata = $this->priceListFilter->getMetadata();

        self::assertSame($expectedMetadata, $metadata);
    }

    public function getMetadataDataProvider(): array
    {
        return [
            'if required, then allowClear is not allowed' => [
                'formViewVars' => ['required' => true, 'populate_default' => false, 'value' => ['value' => 1]],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => false,
                    'value' => ['value' => '1'],
                ],
            ],
            'if not required, then allowClear is allowed' => [
                'formViewVars' => ['required' => false, 'populate_default' => false, 'value' => ['value' => 1]],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => true,
                    'value' => ['value' => '1'],
                ],
            ],
            'if required is not set, then allowClear is not allowed' => [
                'formViewVars' => ['populate_default' => false, 'value' => ['value' => 1]],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => false,
                    'value' => ['value' => '1'],
                ],
            ],
            'if required is not set, value is not set' => [
                'formViewVars' => ['populate_default' => false],
                'expectedMetadata' => [
                    'name' => null,
                    'label' => '',
                    'choices' => [],
                    'lazy' => false,
                    'populateDefault' => false,
                    'allowClear' => false,
                ],
            ],
        ];
    }

    public function testPrepareDataWithNullValue()
    {
        $data = ['value' => null];

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertSame($data, $this->priceListFilter->prepareData($data));
    }

    public function testPrepareDataWithNumericValueAsString()
    {
        $priceList = new PriceList();
        $data = ['value' => '23'];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getReference')
            ->with(PriceList::class, self::identicalTo(23))
            ->willReturn($priceList);

        self::assertSame(['value' => $priceList], $this->priceListFilter->prepareData($data));
    }

    public function testPrepareDataWithNumericValueAsInteger()
    {
        $priceList = new PriceList();
        $data = ['value' => 45];

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getReference')
            ->with(PriceList::class, self::identicalTo(45))
            ->willReturn($priceList);

        self::assertSame(['value' => $priceList], $this->priceListFilter->prepareData($data));
    }
}
