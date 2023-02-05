<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\DataTransformer\ConsentCollectionTransformer;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentCollectionType;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectType;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectWithPriorityType;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\FormBundle\Form\Type\Select2Type;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validation;

class ConsentCollectionTypeTest extends FormIntegrationTestCase
{
    private ConsentCollectionType $formType;

    protected function setUp(): void
    {
        $this->formType = new ConsentCollectionType(
            new ConsentCollectionTransformer(new ConsentConfigConverter($this->createMock(ManagerRegistry::class)))
        );

        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create(ConsentCollectionType::class);
        $form->submit($submitted);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $form->getData());
    }

    public function submitDataProvider(): array
    {
        return [
            [
                'submitted' => [
                    [
                        ConsentConfigConverter::CONSENT_KEY => '1',
                        '_position' => '6',
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => '3',
                        '_position' => '7',
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => '2',
                        '_position' => '5',
                    ],
                ],
                'expected' => [
                    [
                        ConsentConfigConverter::CONSENT_KEY => '1',
                        ConsentConfigConverter::SORT_ORDER_KEY => 6
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => '3',
                        ConsentConfigConverter::SORT_ORDER_KEY => 7
                    ],
                    [
                        ConsentConfigConverter::CONSENT_KEY => '2',
                        ConsentConfigConverter::SORT_ORDER_KEY => 5
                    ],
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                $consent = new Consent();
                ReflectionUtil::setId($consent, $id);

                return $consent;
            });

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(Consent::class)
            ->willReturn($repository);

        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects($this->any())
            ->method('getProperties')
            ->willReturn(['code', 'label']);

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->willReturn($searchHandler);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    new CollectionType(),
                    new ConsentSelectWithPriorityType(),
                    new ConsentSelectType(),
                    new OroEntitySelectOrCreateInlineType(
                        $this->createMock(AuthorizationCheckerInterface::class),
                        $this->createMock(FeatureChecker::class),
                        $this->createMock(ConfigManager::class),
                        $entityManager,
                        $searchRegistry
                    ),
                    new OroJquerySelect2HiddenType(
                        $entityManager,
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    ),
                    new Select2Type($this->formType, 'hidden'),
                    new EntityTypeStub(),
                ],
                [
                    FormType::class => [new SortableExtension()],
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->formType->getParent());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_consent_collection', $this->formType->getBlockPrefix());
    }
}
