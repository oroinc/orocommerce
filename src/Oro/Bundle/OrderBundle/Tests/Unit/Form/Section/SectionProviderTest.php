<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Section;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;

class SectionProviderTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_CLASS = 'form\class';
    private const FORM_NAME = 'form_name';

    /** @var FormRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formRegistry;

    /** @var SectionProvider */
    private $sectionProvider;

    protected function setUp(): void
    {
        $this->formRegistry = $this->createMock(FormRegistryInterface::class);

        $this->sectionProvider = new SectionProvider($this->formRegistry);
    }

    private function configureFormRegistry(string $formClass, string $formName): void
    {
        $formType = $this->createMock(FormTypeInterface::class);
        $formType->expects($this->any())
            ->method('getBlockPrefix')
            ->willReturn($formName);

        $this->formRegistry->expects($this->any())
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
            $this->assertInstanceOf(ArrayCollection::class, $actualSections);

            $this->assertEquals($expectedConfig, $actualSections->toArray());
        }
    }

    public function sectionsDataProvider(): array
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

        $this->assertInstanceOf(ArrayCollection::class, $actualSections);

        $this->assertEquals([], $actualSections->toArray());
    }
}
