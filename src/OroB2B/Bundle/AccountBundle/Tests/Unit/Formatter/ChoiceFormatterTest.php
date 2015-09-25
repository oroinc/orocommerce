<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Formatter;

use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;

use OroB2B\Bundle\AccountBundle\Formatter\ChoiceFormatter;

class ChoiceFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChoiceFormatter
     */
    protected $formatter;

    public function setUp()
    {
        $translator = new StubTranslator();
        $this->formatter = new ChoiceFormatter($translator);
    }

    /**
     * @dataProvider formatChoicesProvider
     * @param $pattern
     * @param $choices
     * @param $expected
     */
    public function testFormatChoices($pattern, $choices, $expected)
    {
        $this->formatter->setTranslationPattern($pattern);
        $this->formatter->setChoices($choices);

        $actual = $this->formatter->formatChoices();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function formatChoicesProvider()
    {
        return [
            [
                '%s',
                ['test_1', 'test_2'],
                [
                    'test_1' => '[trans]test_1[/trans]',
                    'test_2' => '[trans]test_2[/trans]'
                ]
            ],
            [
                'test.%s',
                ['test_1', 'test_2'],
                [
                    'test_1' => '[trans]test.test_1[/trans]',
                    'test_2' => '[trans]test.test_2[/trans]'
                ]
            ],
            [
                '%s',
                function () {
                    return ['test_1', 'test_2'];
                }
                ,
                [
                    'test_1' => '[trans]test_1[/trans]',
                    'test_2' => '[trans]test_2[/trans]'
                ],
                [
                    'test',
                    [],
                    []
                ]
            ]
        ];
    }
}
