<?php

namespace Oro\Bundle\CommerceBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\CustomerDashboardDatagridsProvider;
use Oro\Bundle\CommerceBundle\Form\Type\CustomerDashboardDatagridSelectType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CustomerDashboardDatagridSelectTypeTest extends TestCase
{
    private CustomerDashboardDatagridsProvider&MockObject $customerDashboardDatagridsProvider;

    private CustomerDashboardDatagridSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->customerDashboardDatagridsProvider = $this->createMock(CustomerDashboardDatagridsProvider::class);

        $this->type = new CustomerDashboardDatagridSelectType($this->customerDashboardDatagridsProvider);
    }

    public function testGetParent(): void
    {
        self::assertSame(Select2ChoiceType::class, $this->type->getParent());
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame('oro_commerce_datagrid_content_widget_type_select', $this->type->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $this->customerDashboardDatagridsProvider->expects(self::once())
            ->method('getDatagrids')
            ->willReturn(['oro.type1.label' => 'testType1', 'oro.type2.label' => 'testType2']);

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        self::assertSame(
            [
                'choices' => ['oro.type1.label' => 'testType1', 'oro.type2.label' => 'testType2'],
                'placeholder' => 'oro.commerce.content_widget_type.customer_dashboard_datagrid.form.choose_datagrid',
            ],
            $resolver->resolve()
        );
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(Form::class);

        $this->type->buildView($view, $form, ['configs' => [], 'choices' => []]);

        self::assertSame(
            [
                'value' => null,
                'attr' => [],
                'configs' => [
                    'placeholder' =>
                        'oro.commerce.content_widget_type.customer_dashboard_datagrid.form.no_available_datagrid'
                ],
            ],
            $view->vars
        );
    }

    public function testBuildViewWithEmptyChoices(): void
    {
        $view = new FormView();
        $form = $this->createMock(Form::class);

        $this->type->buildView($view, $form, ['configs' => [], 'choices' => ['label' => 'id']]);

        self::assertSame(
            [
                'value' => null,
                'attr' => [],
                'configs' => [],
            ],
            $view->vars
        );
    }
}
