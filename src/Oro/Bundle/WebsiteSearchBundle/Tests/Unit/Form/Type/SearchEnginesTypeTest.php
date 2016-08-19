<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Intl;

use Oro\Bundle\WebsiteSearchBundle\Form\Type\SearchEnginesType;
use Oro\Bundle\WebsiteSearchBundle\Provider\SearchEnginesProvider;

class SearchEnginesTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SearchEnginesType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SearchEnginesProvider
     */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this->getMockBuilder('Oro\Bundle\WebsiteSearchBundle\Provider\SearchEnginesProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new SearchEnginesType($this->provider);
    }

    public function testGetName()
    {
        $this->assertEquals(SearchEnginesType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $engines
     * @param array $expectedOptions
     * @param string $submittedData
     */
    public function testSubmit(array $engines, array $expectedOptions, $submittedData)
    {
        $inputOptions = ['choices' => $engines];
        $this->provider->expects($this->once())
            ->method('getEngines')
            ->willReturn($engines);

        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
        }
        $this->assertEquals($expectedOptions['choices'], $form->createView()->vars['choices']);

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider()
    {
        return [
            'ORM only' => [
                'engines' => [SearchEnginesProvider::SEARCH_ENGINE_ORM],
                'expectedOptions' => [
                    'choices' => [
                        new ChoiceView(0, 0, SearchEnginesProvider::SEARCH_ENGINE_ORM)
                    ]
                ],
                'submittedData' => SearchEnginesProvider::SEARCH_ENGINE_ORM
            ],
            'ORM and ES' => [
                'engines' => [SearchEnginesProvider::SEARCH_ENGINE_ORM, SearchEnginesProvider::SEARCH_ENGINE_ES],
                'expectedOptions' => [
                    'choices' => [
                        new ChoiceView(0, 0, SearchEnginesProvider::SEARCH_ENGINE_ORM),
                        new ChoiceView(0, 0, SearchEnginesProvider::SEARCH_ENGINE_ES),
                    ]
                ],
                'submittedData' => SearchEnginesProvider::SEARCH_ENGINE_ORM
            ],
        ];
    }
}
