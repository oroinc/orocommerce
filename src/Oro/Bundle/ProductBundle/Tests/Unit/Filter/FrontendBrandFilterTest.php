<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Filter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Repository\BrandRepository;
use Oro\Bundle\ProductBundle\Filter\FrontendBrandFilter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FrontendBrandFilterTest extends TestCase
{
    use EntityTrait;

    private FormFactoryInterface|MockObject $formFactory;

    private ManagerRegistry|MockObject $doctrine;

    private WebsiteManager|MockObject $websiteManager;

    private FrontendBrandFilter $filter;

    private function createForm(): FormInterface|MockObject
    {
        $typeFormView = new FormView();
        $typeFormView->vars['choices'] = [];

        $valueFormView = new FormView();
        $valueFormView->vars['choices'] = [];
        $valueFormView->vars['multiple'] = true;

        $formView = new FormView();
        $formView->children['type'] = $typeFormView;
        $formView->children['value'] = $valueFormView;
        $formView->vars['populate_default'] = true;

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        return $form;
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->filter = new FrontendBrandFilter($this->formFactory, new FilterUtility(), $this->doctrine);
    }

    public function testGetMetadata(): void
    {
        $repository = $this->createMock(BrandRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Brand::class)
            ->willReturn($repository);

        $repository
            ->expects($this->once())
            ->method('getBrandQueryBuilder')
            ->willReturn($queryBuilder);

        $this->filter->init('test', [FilterUtility::DATA_NAME_KEY => 'field', 'class' => \stdClass::class]);

        $form = $this->createForm();

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                SearchEntityFilterType::class,
                [],
                [
                    'csrf_protection' => false,
                    'class' => \stdClass::class,
                    'field_options' => ['query_builder' => $queryBuilder]
                ]
            )
            ->willReturn($form);

        $this->assertEquals(
            [
                'name' => 'test',
                'label' => 'Test',
                'choices' => [],
                'lazy' => false,
                'populateDefault' => true,
                'type' => 'multichoice',
            ],
            $this->filter->getMetadata()
        );
    }
}
