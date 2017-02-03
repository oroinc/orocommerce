<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedSlugWithRedirectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var LocalizedSlugWithRedirectType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LocalizedSlugWithRedirectType($this->configManager);
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizedSlugWithRedirectType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(LocalizedSlugWithRedirectType::NAME, $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [
                    LocalizedSlugWithRedirectType::SLUG_PROTOTYPES_FIELD_NAME,
                    LocalizedSlugType::NAME,
                    [
                        'required' => false,
                        'options' => ['constraints' => [new UrlSafe()]],
                        'label' => false,
                        'source_field' => 'field',
                        'slug_suggestion_enabled' => true,
                    ]
                ],
                [
                    LocalizedSlugWithRedirectType::CREATE_REDIRECT_FIELD_NAME,
                    CheckboxType::class,
                    [
                        'label' => 'oro.redirect.confirm_slug_change.checkbox_label',
                        'data' => true,
                    ]
                ]
            )
            ->willReturnSelf();

        $this->formType->buildForm(
            $builder,
            ['source_field' => 'field', 'slug_suggestion_enabled' => true]
        );
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->callback(
                function (array $options) {
                    $this->assertEquals(SlugPrototypesWithRedirect::class, $options['data_class']);
                    $this->assertFalse($options['slug_suggestion_enabled']);
                    $this->assertFalse($options['create_redirect_enabled']);

                    return true;
                }
            )
        );
        $resolver->expects($this->once())->method('setRequired')->with('source_field');

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider buildViewProvider
     * @param bool $createRedirectEnabled
     * @param string $strategy
     * @param SlugPrototypesWithRedirect $data
     * @param bool $expectDisabled
     */
    public function testBuildView($createRedirectEnabled, $strategy, SlugPrototypesWithRedirect $data, $expectDisabled)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
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

        $this->formType->buildView($view, $form, $options);

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

    public function buildViewProvider()
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
     * @return PersistentCollection
     */
    protected function createPersistentCollection()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock();
        $collection = new ArrayCollection(['some-entry']);

        return new PersistentCollection($em, $classMetadata, $collection);
    }
}
