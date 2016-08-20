<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Section;

use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;

class SectionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SectionProvider */
    protected $sectionProvider;

    protected function setUp()
    {
        $this->sectionProvider = new SectionProvider();
    }

    protected function tearDown()
    {
        unset($this->sectionProvider);
    }

    /**
     * @param array $sections
     * @param array $sectionsModifiers
     * @param array $expectedSections
     *
     * @dataProvider sectionsDataProvider
     */
    public function testSections(array $sections = [], array $sectionsModifiers = [], array $expectedSections = [])
    {
        foreach ($sections as $formName => $config) {
            $this->sectionProvider->addSections($formName, $config);
        }

        foreach ($sectionsModifiers as $formName => $config) {
            $this->sectionProvider->addSections($formName, $config);
        }

        foreach ($expectedSections as $formName => $expectedConfig) {
            $actualSections = $this->sectionProvider->getSections($formName);
            $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actualSections);

            $this->assertEquals($expectedConfig, $actualSections->toArray());
        }
    }

    /**
     * @return array
     */
    public function sectionsDataProvider()
    {
        return [
            'sections are empty' => [
                ['form_name' => ['section1' => []]],
                [],
                ['form_name' => ['section1' => []]],
            ],
            'test order' => [
                ['form_name' => ['section1' => ['order' => 10], 'section2' => ['order' => 5]]],
                [],
                ['form_name' => ['section2' => ['order' => 5], 'section1' => ['order' => 10]]],
            ],
            'test section override' => [
                ['form_name' => ['section1' => ['order' => 10], 'section2' => ['order' => 5]]],
                ['form_name' => ['section1' => ['order' => 20], 'section2' => ['order' => 10]]],
                ['form_name' => ['section2' => ['order' => 10], 'section1' => ['order' => 20]]],
            ],
        ];
    }

    public function testNotConfiguredSection()
    {
        $actualSections = $this->sectionProvider->getSections('not_configured');

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actualSections);

        $this->assertEquals([], $actualSections->toArray());
    }
}
