<?php

namespace Oro\Bundle\FallbackBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FallbackBundle\Form\Type\WebsiteCollectionType;
use Oro\Bundle\FallbackBundle\Form\Type\WebsitePropertyType;
use Oro\Bundle\FallbackBundle\Tests\Unit\Form\Type\Stub\CheckboxTypeStub;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackPropertyType;
use Oro\Bundle\LocaleBundle\Form\Type\FallbackValueType;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WebsitePropertyTypeTest extends FormIntegrationTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $websiteCollection = new WebsiteCollectionType($this->registry);
        $websiteCollection->setWebsiteClass(Website::class);

        return [
            new PreloadedExtension(
                [
                    new FallbackPropertyType($this->createMock(TranslatorInterface::class)),
                    new FallbackValueType(),
                    $websiteCollection,
                    new CheckboxTypeStub(),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            )
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $options,
        ?array $defaultData,
        array $viewData,
        ?array $submittedData,
        array $expectedData
    ) {
        $this->setRegistryExpectations();

        $form = $this->factory->create(WebsitePropertyType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        foreach ($viewData as $field => $data) {
            $this->assertEquals($data, $form->get($field)->getViewData());
        }

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            'text with null data' => [
                'entry_options' => ['entry_type' => TextType::class],
                'defaultData' => null,
                'viewData' => [
                    WebsitePropertyType::FIELD_DEFAULT => null,
                    WebsitePropertyType::FIELD_WEBSITES => [
                        1 => new FallbackType(FallbackType::SYSTEM),
                        2 => new FallbackType(FallbackType::SYSTEM),
                        3 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
                'submittedData' => null,
                'expectedData' => [
                    null => null,
                    1    => null,
                    2    => null,
                    3    => null,
                ],
            ],
            'checkbox with full data' => [
                'entry_options' => ['entry_type' => CheckboxTypeStub::class, 'entry_options' => ['value' => 't']],
                'defaultData' => [
                    null => true,
                    1    => false,
                    2    => new FallbackType(FallbackType::SYSTEM),
                    3    => new FallbackType(FallbackType::SYSTEM),
                ],
                'viewData' => [
                    WebsitePropertyType::FIELD_DEFAULT => 't',
                    WebsitePropertyType::FIELD_WEBSITES => [
                        1 => '',
                        2 => new FallbackType(FallbackType::SYSTEM),
                        3 => new FallbackType(FallbackType::SYSTEM),
                    ]
                ],
                'submittedData' => [
                    WebsitePropertyType::FIELD_DEFAULT => 't',
                    WebsitePropertyType::FIELD_WEBSITES => [
                        1 => ['fallback' => FallbackType::SYSTEM, 'use_fallback' => true],
                        2 => ['fallback' => '', 'use_fallback' => true],
                        3 => ['fallback' => FallbackType::SYSTEM, 'use_fallback' => true],
                    ]
                ],
                'expectedData' => [
                    null => true,
                    1    => new FallbackType(FallbackType::SYSTEM),
                    2    => false,
                    3    => new FallbackType(FallbackType::SYSTEM),
                ],
            ],
        ];
    }

    private function setRegistryExpectations()
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($this->getWebsites());

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with('website.id', 'ASC')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('website')
            ->willReturn($queryBuilder);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($repository);
    }

    /**
     * @return Website[]
     */
    private function getWebsites(): array
    {
        $first  = $this->createWebsite(1, 'first');
        $second = $this->createWebsite(2, 'second');
        $third  = $this->createWebsite(3, 'third');

        return [$first, $second, $third];
    }

    private function createWebsite(int $id, string $name): Website
    {
        $website = $this->createMock(Website::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $website->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $website;
    }
}
