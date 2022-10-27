<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class ConfirmSlugChangeFormHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ConfirmSlugChangeFormHelper
     */
    private $helper;

    protected function setUp(): void
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = new ConfirmSlugChangeFormHelper($this->configManager);
    }

    /**
     * @dataProvider addConfirmSlugChangeOptionsLocalizedProvider
     * @param bool $createRedirectEnabled
     * @param string $strategy
     * @param SlugPrototypesWithRedirect $data
     * @param bool $expectDisabled
     */
    public function testAddConfirmSlugChangeOptionsLocalized(
        $createRedirectEnabled,
        $strategy,
        SlugPrototypesWithRedirect $data,
        $expectDisabled
    ) {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'test',
            'create_redirect_enabled' => $createRedirectEnabled,
            'slug_suggestion_enabled' => false,
        ];

        $this->helper->addConfirmSlugChangeOptionsLocalized($view, $form, $options);

        $this->assertArrayHasKey('confirm_slug_change_component_options', $view->vars);
        $this->assertEquals(
            '[name^="form-name[target-name]['.LocalizedSlugWithRedirectType::SLUG_PROTOTYPES_FIELD_NAME.'][values]"]',
            $view->vars['confirm_slug_change_component_options']['slugFields']
        );
        $this->assertEquals(
            '[name^="form-name[target-name]['.LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME.']"]',
            $view->vars['confirm_slug_change_component_options']['createRedirectCheckbox']
        );
        $this->assertEquals($expectDisabled, $view->vars['confirm_slug_change_component_options']['disabled']);
    }

    public function addConfirmSlugChangeOptionsLocalizedProvider()
    {
        return [
            'create redirect disabled true by option' => [
                'createRedirectEnabled' => false,
                'strategy' => 'any',
                'data' => new SlugPrototypesWithRedirect(new ArrayCollection()),
                'expectDisabled' => true,
            ],
            'create redirect disabled true by strategy' => [
                'createRedirectEnabled' => true,
                'strategy' => 'any',
                'data' => new SlugPrototypesWithRedirect(new ArrayCollection()),
                'expectDisabled' => true,
            ],
            'create redirect disabled true by slugPrototypes collection empty' => [
                'createRedirectEnabled' => true,
                'strategy' => Configuration::STRATEGY_ASK,
                'data' => new SlugPrototypesWithRedirect(new ArrayCollection()),
                'expectDisabled' => true,
            ],
            'create redirect disabled false' => [
                'createRedirectEnabled' => true,
                'strategy' => Configuration::STRATEGY_ASK,
                'data' => new SlugPrototypesWithRedirect(new ArrayCollection(['some data'])),
                'expectDisabled' => false,
            ],
        ];
    }

    /**
     * @dataProvider addConfirmSlugChangeOptionsProvider
     * @param bool $createRedirectEnabled
     * @param string $strategy
     * @param TextSlugPrototypeWithRedirect $data
     * @param bool $expectDisabled
     */
    public function testAddConfirmSlugChangeOptions(
        $createRedirectEnabled,
        $strategy,
        TextSlugPrototypeWithRedirect $data,
        $expectDisabled
    ) {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($data);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        $view = new FormView();
        $view->vars['full_name'] = 'form-name[target-name]';
        $options = [
            'source_field' => 'test',
            'create_redirect_enabled' => $createRedirectEnabled,
            'slug_suggestion_enabled' => false,
        ];

        $this->helper->addConfirmSlugChangeOptions($view, $form, $options);

        $this->assertArrayHasKey('confirm_slug_change_component_options', $view->vars);
        $this->assertEquals(
            '[name^="form-name[target-name]['.SlugWithRedirectType::TEXT_SLUG_PROTOTYPE_FIELD_NAME.']"]',
            $view->vars['confirm_slug_change_component_options']['slugFields']
        );
        $this->assertEquals(
            '[name^="form-name[target-name]['.SlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME.']"]',
            $view->vars['confirm_slug_change_component_options']['createRedirectCheckbox']
        );
        $this->assertEquals($expectDisabled, $view->vars['confirm_slug_change_component_options']['disabled']);
    }

    public function addConfirmSlugChangeOptionsProvider()
    {
        $emptyText = '';
        $text = 'text';
        return [
            'create redirect disabled true by option' => [
                'createRedirectEnabled' => false,
                'strategy' => 'any',
                'data' => new TextSlugPrototypeWithRedirect($text),
                'expectDisabled' => true,
            ],
            'create redirect disabled true by strategy' => [
                'createRedirectEnabled' => true,
                'strategy' => 'any',
                'data' => new TextSlugPrototypeWithRedirect($text),
                'expectDisabled' => true,
            ],
            'create redirect disabled true by textSlugPrototype field empty' => [
                'createRedirectEnabled' => true,
                'strategy' => Configuration::STRATEGY_ASK,
                'data' => new TextSlugPrototypeWithRedirect($emptyText),
                'expectDisabled' => true,
            ],
            'create redirect disabled false' => [
                'createRedirectEnabled' => true,
                'strategy' => Configuration::STRATEGY_ASK,
                'data' => new TextSlugPrototypeWithRedirect($text),
                'expectDisabled' => false,
            ],
        ];
    }

    /**
     * @return PersistentCollection
     */
    protected function createPersistentCollection()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $collection = new ArrayCollection(['some-entry']);

        return new PersistentCollection($em, $classMetadata, $collection);
    }
}
