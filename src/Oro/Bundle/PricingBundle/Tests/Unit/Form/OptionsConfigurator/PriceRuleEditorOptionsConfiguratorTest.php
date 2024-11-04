<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\OptionsConfigurator;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProviderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;

class PriceRuleEditorOptionsConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var AutocompleteFieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $autocompleteFieldsProvider;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $twig;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var PriceRuleEditorOptionsConfigurator */
    private $configurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->autocompleteFieldsProvider = $this->createMock(AutocompleteFieldsProviderInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);

        $this->configurator = new PriceRuleEditorOptionsConfigurator(
            $this->autocompleteFieldsProvider,
            $this->formFactory,
            $this->twig,
            $this->entityAliasResolver
        );
    }

    public function testConfigureOptionsWithoutPriceList()
    {
        $optionsResolver = new OptionsResolver();
        $this->configurator->configureOptions($optionsResolver);

        $this->formFactory->expects($this->never())
            ->method('createNamed');

        $this->twig->expects($this->never())
            ->method('render')
            ->with('@OroPricing/Form/form_widget.html.twig');

        $this->entityAliasResolver->expects($this->never())
            ->method('getAlias');

        $expected = [
            'numericOnly' => false,
            'supportedNames' => [],
            'dataProviderConfig' => null,
            'dataSource' => [],
        ];
        $this->assertEquals($expected, $optionsResolver->resolve([]));
    }

    public function testConfigureOptionsWithPriceList()
    {
        $optionsResolver = new OptionsResolver();
        $this->configurator->configureOptions($optionsResolver);

        $this->assertFormCalled();
        $selectHtml = '<select/>';
        $this->twig->expects($this->once())
            ->method('render')
            ->with('@OroPricing/Form/form_widget.html.twig')
            ->willReturn($selectHtml);

        $this->entityAliasResolver->expects($this->once())
            ->method('getAlias')
            ->with($this->stringContains('PriceList'))
            ->willReturn('price_list');

        $this->autocompleteFieldsProvider->expects($this->never())
            ->method('getRootEntities');

        $this->autocompleteFieldsProvider->expects($this->never())
            ->method('getDataProviderConfig');

        $options = [
            'numericOnly' => false,
            'supportedNames' => ['price_list'],
            'dataProviderConfig' => [
                'include' => [['type' => 'integer']],
            ],
            'dataSource' => [],
        ];

        $expected = [
            'numericOnly' => false,
            'supportedNames' => ['price_list'],
            'dataProviderConfig' => [
                'include' => [['type' => 'integer']]
            ],
            'dataSource' => [
                'price_list' => $selectHtml
            ],
        ];
        $this->assertEquals($expected, $optionsResolver->resolve($options));
    }

    public function testConfigureOptionsWithPriceListEntitiesFilledByProvider()
    {
        $optionsResolver = new OptionsResolver();
        $this->configurator->configureOptions($optionsResolver);

        $this->assertFormCalled();
        $selectHtml = '<select/>';
        $this->twig->expects($this->once())
            ->method('render')
            ->with('@OroPricing/Form/form_widget.html.twig')
            ->willReturn($selectHtml);

        $this->entityAliasResolver->expects($this->once())
            ->method('getAlias')
            ->with($this->stringContains('PriceList'))
            ->willReturn('price_list');

        $this->autocompleteFieldsProvider->expects($this->once())
            ->method('getRootEntities')
            ->willReturn([PriceList::class => 'price_list']);

        $this->autocompleteFieldsProvider->expects($this->once())
            ->method('getDataProviderConfig')
            ->with(false)
            ->willReturn(
                ['include' => [['type' => 'integer']]]
            );

        $options = [
            'numericOnly' => false,
            'supportedNames' => [],
            'dataProviderConfig' => [],
            'dataSource' => [],
        ];

        $expected = [
            'numericOnly' => false,
            'supportedNames' => ['price_list'],
            'dataProviderConfig' => [
                'include' => [['type' => 'integer']]
            ],
            'dataSource' => [
                'price_list' => $selectHtml
            ],
        ];
        $this->assertEquals($expected, $optionsResolver->resolve($options));
    }

    public function testConfigureOptionsWithPriceListTwigFailed()
    {
        $optionsResolver = new OptionsResolver();
        $this->configurator->configureOptions($optionsResolver);

        $this->assertFormCalled();
        $this->twig->expects($this->once())
            ->method('render')
            ->with('@OroPricing/Form/form_widget.html.twig')
            ->willThrowException(new \Exception('test'));

        $this->entityAliasResolver->expects($this->once())
            ->method('getAlias')
            ->with($this->stringContains('PriceList'))
            ->willReturn('price_list');

        $options = [
            'numericOnly' => false,
            'supportedNames' => ['price_list'],
            'dataProviderConfig' => [
                'include' => [['type' => 'integer']],
            ],
            'dataSource' => [],
        ];

        $expected = [
            'numericOnly' => false,
            'supportedNames' => ['price_list'],
            'dataProviderConfig' => [
                'include' => [['type' => 'integer']],
            ],
            'dataSource' => [],

        ];
        $this->assertEquals($expected, $optionsResolver->resolve($options));
    }

    public function testLimitNumericOnlyRulesNonNumeric()
    {
        $view = new FormView();
        $view->vars['attr']['data-page-component-options'] = json_encode([]);
        $options = ['numericOnly' => false];

        $this->configurator->limitNumericOnlyRules($view, $options);

        $this->assertJsonStringEqualsJsonString(json_encode([]), $view->vars['attr']['data-page-component-options']);
    }

    public function testLimitNumericOnlyRulesNumeric()
    {
        $view = new FormView();
        $view->vars['attr']['data-page-component-options'] = json_encode([]);
        $options = ['numericOnly' => true];

        $this->configurator->limitNumericOnlyRules($view, $options);

        $this->assertJsonStringEqualsJsonString(
            json_encode(['allowedOperations' => ['math']]),
            $view->vars['attr']['data-page-component-options']
        );
    }

    private function assertFormCalled()
    {
        $priceListSelectFormView = $this->createMock(FormView::class);
        $priceListSelectForm = $this->createMock(FormInterface::class);
        $priceListSelectForm->expects($this->once())
            ->method('createView')
            ->willReturn($priceListSelectFormView);
        $this->formFactory->expects($this->once())
            ->method('createNamed')
            ->willReturn($priceListSelectForm);
    }
}
