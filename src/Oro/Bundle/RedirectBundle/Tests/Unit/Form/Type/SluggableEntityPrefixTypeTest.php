<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type;

use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Form\Storage\RedirectStorage;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Form\Type\SluggableEntityPrefixType;
use Oro\Bundle\RedirectBundle\Model\PrefixWithRedirect;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\ConstraintViolationList;

class SluggableEntityPrefixTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RedirectStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var SluggableEntityPrefixType
     */
    protected $formType;

    protected function setUp()
    {
        $this->storage = $this->createMock(RedirectStorage::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * @var \Symfony\Component\Validator\ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $validator
         */
        $validator = $this->createMock('\Symfony\Component\Validator\ValidatorInterface');
        $validator->expects($this->any())
            ->method('validate')
            ->will($this->returnValue(new ConstraintViolationList()));

        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtension(new FormTypeValidatorExtension($validator))
            ->getFormFactory();

        $this->formType = new SluggableEntityPrefixType($this->storage, $this->configManager);
    }

    public function testGetName()
    {
        $this->assertEquals(SluggableEntityPrefixType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SluggableEntityPrefixType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param $defaultData
     * @param $submittedData
     * @param $expectedData
     */
    public function testSubmit($defaultData, $submittedData, $expectedData)
    {
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->expects($this->once())
            ->method('getName')
            ->willReturn('test___config');

        $form = $this->factory->create($this->formType, $defaultData);
        $form->setParent($parentForm);

        $this->assertEquals($defaultData, $form->getData());

        $this->storage->expects($this->once())
            ->method('addPrefix')
            ->with('test.config', $expectedData);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var PrefixWithRedirect $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
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
            ]
        ];
    }

    /**
     * @dataProvider finishViewDataProvider
     *
     * @param string $strategy
     * @param bool $isAskStrategy
     */
    public function testFinishView($strategy, $isAskStrategy)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

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

    /**
     * @return array
     */
    public function finishViewDataProvider()
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
