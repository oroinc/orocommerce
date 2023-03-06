<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectType;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectWithPriorityType;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Extension\SortableExtension;
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

class ConsentSelectWithPriorityTypeTest extends FormIntegrationTestCase
{
    private ConsentSelectWithPriorityType $formType;

    protected function setUp(): void
    {
        $this->formType = new ConsentSelectWithPriorityType();
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getConsent($id);
            });

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(Consent::class)
            ->willReturn($repository);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

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
                    new EntityTypeStub(),
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
                ],
                [
                    FormType::class => [new SortableExtension()],
                ]
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    private function getConsent(int $id): Consent
    {
        $consent = new Consent();
        ReflectionUtil::setId($consent, $id);

        return $consent;
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submittedData, ConsentConfig $expectedData)
    {
        $form = $this->factory->create(ConsentSelectWithPriorityType::class, new ConsentConfig(), [
            'data_class' => ConsentConfig::class,
        ]);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $expectedConsent = $this->getConsent(2);

        return [
            'without default data' => [
                'submittedData' => [
                    'consent' => 2,
                    '_position' => 100,
                ],
                'expectedData' => (new ConsentConfig())
                    ->setSortOrder(100)
                    ->setConsent($expectedConsent),
            ],
            'with default data' => [
                'submittedData' => [
                    'consent' => 2,
                    '_position' => 100,
                ],
                'expectedData' => (new ConsentConfig())
                    ->setSortOrder(100)
                    ->setConsent($expectedConsent),
            ],
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_consent_select_with_priority', $this->formType->getBlockPrefix());
    }
}
