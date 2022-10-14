<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\RedirectBundle\Form\Type\SluggableEntityPrefixType;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SluggableEntityPrefixTypeTest extends FormIntegrationTestCase
{
    /** @var RedirectStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SluggableEntityPrefixType */
    private $formType;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(RedirectStorage::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->formType = new SluggableEntityPrefixType($this->storage, $this->configManager);

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    SluggableEntityPrefixType::class => $this->formType
                ],
                []
            ),
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SluggableEntityPrefixType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        ?PrefixWithRedirect $defaultData,
        array $submittedData,
        PrefixWithRedirect $expectedData
    ) {
        $parentForm = $this->createMock(FormInterface::class);
        $form = $this->factory->create(SluggableEntityPrefixType::class, $defaultData);
        $form->setParent($parentForm);

        $this->assertEquals($defaultData, $form->getData());

        if ($defaultData) {
            $parentForm->expects($this->once())
                ->method('getName')
                ->willReturn('test___config');
            $this->storage->expects($this->once())
                ->method('addPrefix')
                ->with('test.config', $expectedData);
        } else {
            $this->storage->expects($this->never())
                ->method('addPrefix');
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        /** @var PrefixWithRedirect $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    public function submitDataProvider(): array
    {
        return [
            'create new' => [
                'defaultData' => new PrefixWithRedirect(),
                'submittedData' => [
                    'prefix' => 'some-prefix',
                    'createRedirect' => true
                ],
                'expectedData' => (new PrefixWithRedirect())->setPrefix('some-prefix')->setCreateRedirect(true)
            ],
            'edit existing' => [
                'defaultData' => (new PrefixWithRedirect())->setPrefix('some-prefix')->setCreateRedirect(true),
                'submittedData' => [
                    'prefix' => 'another-prefix',
                    'createRedirect' => false
                ],
                'expectedData' => (new PrefixWithRedirect())->setPrefix('another-prefix')->setCreateRedirect(false)
            ],
            'null data' => [
                'defaultData' => null,
                'submittedData' => [
                    'prefix' => 'some-prefix',
                    'createRedirect' => true
                ],
                'expectedData' => (new PrefixWithRedirect())->setPrefix('some-prefix')->setCreateRedirect(true)
            ],
        ];
    }

    /**
     * @dataProvider finishViewDataProvider
     */
    public function testFinishView(string $strategy, bool $isAskStrategy)
    {
        $form = $this->createMock(FormInterface::class);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.redirect_generation_strategy')
            ->willReturn($strategy);

        $formView = new FormView();
        $this->formType->finishView($formView, $form, []);

        $this->assertArrayHasKey('isAskStrategy', $formView->vars);
        $this->assertEquals($isAskStrategy, $formView->vars['isAskStrategy']);

        $this->assertArrayHasKey('askStrategyName', $formView->vars);
        $this->assertEquals(Configuration::STRATEGY_ASK, $formView->vars['askStrategyName']);
    }

    public function finishViewDataProvider(): array
    {
        return [
            'ask strategy' => [
                'strategy' => Configuration::STRATEGY_ASK,
                'isAskStrategy' => true
            ],
            'not ask strategy' => [
                'strategy' => Configuration::STRATEGY_NEVER,
                'isAskStrategy' => false
            ]
        ];
    }
}
