<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Oro\Bundle\RedirectBundle\Helper\SlugifyFormHelper;
use Symfony\Component\Form\FormView;

class SlugifyFormHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugifyFormHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->helper = new SlugifyFormHelper();
    }

    public function testAddSlugifyOptionsLocalized()
    {
        $viewParent = new FormView();
        $viewParent->vars['full_name'] = 'form-name';
        $view = new FormView($viewParent);
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'source-name',
            'slugify_route' => 'some-route',
            'slug_suggestion_enabled' => true,
        ];

        $this->helper->addSlugifyOptionsLocalized($view, $options);
        $this->assertArrayHasKey('slugify_component_options', $view->vars);
        $this->assertEquals(
            '[name^="form-name[source-name][values]"]',
            $view->vars['slugify_component_options']['source']
        );
        $this->assertEquals(
            '[name^="form-name[target-name][values]"]',
            $view->vars['slugify_component_options']['target']
        );
        $this->assertEquals(
            'some-route',
            $view->vars['slugify_component_options']['slugify_route']
        );
    }

    public function testAddSlugifyOptions()
    {
        $viewParent = new FormView();
        $viewParent->vars['full_name'] = 'form-name';
        $view = new FormView($viewParent);
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'source-name',
            'slugify_route' => 'some-route',
            'slug_suggestion_enabled' => true,
        ];

        $this->helper->addSlugifyOptions($view, $options);
        $this->assertArrayHasKey('slugify_component_options', $view->vars);
        $this->assertEquals(
            '[name^="form-name[source-name]"]',
            $view->vars['slugify_component_options']['source']
        );
        $this->assertEquals(
            '[name^="form-name[target-name]"]',
            $view->vars['slugify_component_options']['target']
        );
        $this->assertEquals(
            'some-route',
            $view->vars['slugify_component_options']['slugify_route']
        );
    }

    /**
     * @dataProvider addSlugifyOptionsFailedProvider
     */
    public function testAddSlugifyOptionsFailed(bool $slugSuggestionEnabled, ?string $sourceField, ?FormView $parent)
    {
        $localizedView = new FormView($parent);
        $options = [
            'source_field' => $sourceField,
            'slug_suggestion_enabled' => $slugSuggestionEnabled,
        ];

        $this->helper->addSlugifyOptionsLocalized($localizedView, $options);
        $this->assertArrayNotHasKey('slugify_component_options', $localizedView->vars);

        $view = new FormView($parent);
        $this->helper->addSlugifyOptions($view, $options);
        $this->assertArrayNotHasKey('slugify_component_options', $view->vars);
    }

    public function addSlugifyOptionsFailedProvider(): array
    {
        return [
            'slug suggestion disabled' => [
                'slugSuggestionEnabled' => false,
                'sourceField' => 'field',
                'parent' => new FormView(),
            ],
            'source_field does not exist' => [
                'slugSuggestionEnabled' => true,
                'sourceField' => null,
                'parent' => new FormView(),
            ],
            'no parent' => [
                'slugSuggestionEnabled' => true,
                'sourceField' => 'field',
                'parent' => null,
            ],
        ];
    }
}
