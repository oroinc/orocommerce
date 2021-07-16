<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Section;

use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;

class SectionProviderTest extends \PHPUnit\Framework\TestCase
{
    const FORM_CLASS = 'form\class';
    const FORM_NAME = 'form_name';

    /** @var SectionProvider */
    protected $sectionProvider;

    /** @var FormRegistryInterface */
    protected $formRegistry;

    protected function setUp(): void
    {
        $this->formRegistry = $this->createMock(FormRegistryInterface::class);

        $this->sectionProvider = new SectionProvider($this->formRegistry);
    }

    protected function tearDown(): void
    {
        unset($this->sectionProvider);
    }

    /**
     * @param string $formClass
     * @param string $formName
     */
    private function configureFormRegistry($formClass, $formName)
    {
        $formType = $this->createMock(FormTypeInterface::class);
        $formType
            ->expects($this->any())
            ->method('getBlockPrefix')
            ->willReturn($formName);

        $this->formRegistry
            ->expects($this->any())
            ->method('getType')
            ->with($formClass)
            ->willReturn($formType);
    }

    /**
     * @dataProvider sectionsDataProvider
     */
    public function testSections(array $sections = [], array $sectionsModifiers = [], array $expectedSections = [])
    {
        $this->configureFormRegistry(self::FORM_CLASS, self::FORM_NAME);

        foreach ($sections as $formClass => $config) {
            $this->sectionProvider->addSections($formClass, $config);
        }

        foreach ($sectionsModifiers as $formClass => $config) {
            $this->sectionProvider->addSections($formClass, $config);
        }

        foreach ($expectedSections as $formClass => $expectedConfig) {
            $actualSections = $this->sectionProvider->getSections($formClass);
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
                [self::FORM_CLASS => ['section1' => []]],
                [],
                [self::FORM_CLASS => ['section1' => []]],
            ],
            'test order' => [
                [self::FORM_CLASS => ['section1' => ['order' => 10], 'section2' => ['order' => 5]]],
                [],
                [self::FORM_CLASS => ['section2' => ['order' => 5], 'section1' => ['order' => 10]]],
            ],
            'test section override' => [
                [self::FORM_CLASS => ['section1' => ['order' => 10], 'section2' => ['order' => 5]]],
                [self::FORM_CLASS => ['section1' => ['order' => 20], 'section2' => ['order' => 10]]],
                [self::FORM_CLASS => ['section2' => ['order' => 10], 'section1' => ['order' => 20]]],
            ],
        ];
    }

    public function testNotConfiguredSection()
    {
        $this->configureFormRegistry('not\configured\class', 'not_configured_name');
        $actualSections = $this->sectionProvider->getSections('not\configured\class');

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actualSections);

        $this->assertEquals([], $actualSections->toArray());
    }
}
