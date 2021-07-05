<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressRecipientsType;
use Oro\Bundle\EmailBundle\Form\Type\EmailOriginFromType;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Extension\QuoteEmailTemplateExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\NullContext;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\DataCollectorTranslator;

class QuoteEmailTemplateExtensionTest extends FormIntegrationTestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EmailTemplateRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var QuoteEmailTemplateExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->repository = $this->createMock(EmailTemplateRepository::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn(new Organization());

        $this->extension = new QuoteEmailTemplateExtension($this->tokenAccessor, $this->featureChecker);

        parent::setUp();
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(bool $isFeatureEnabled, array $excludeNames): void
    {
        $data = new Email();
        $data->setEntityClass(Quote::class);

        $form = $this->factory->create(EmailType::class);
        $form->setData($data);

        $template = $form->get('template');
        $templateOptions = $template->getConfig()->getOptions();

        $this->assertArrayHasKey('selectedEntity', $templateOptions);
        $this->assertEquals(Quote::class, $templateOptions['selectedEntity']);
        $this->assertArrayHasKey('query_builder', $templateOptions);
        $this->assertInstanceOf(\Closure::class, $templateOptions['query_builder']);

        $qb = $this->createMock(QueryBuilder::class);

        $this->repository->expects($this->once())
            ->method('getEntityTemplatesQueryBuilder')
            ->with(
                Quote::class,
                $this->tokenAccessor->getOrganization(),
                false,
                false,
                true,
                $excludeNames
            )
            ->willReturn($qb);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('guest_quote')
            ->willReturn($isFeatureEnabled);

        $this->assertSame($qb, $templateOptions['query_builder']($this->repository));
    }

    public function buildFormDataProvider(): array
    {
        return [
            [
                'isFeatureEnabled' => false,
                'excludeNames' => ['quote_email_link_guest']
            ],
            [
                'isFeatureEnabled' => true,
                'excludeNames' => []
            ]
        ];
    }

    public function testBuildFormForUnsupportedClass(): void
    {
        $data = new Email();
        $data->setEntityClass(\stdClass::class);

        $form = $this->factory->create(EmailType::class);
        $form->setData($data);

        $template = $form->get('template');
        $templateOptions = $template->getConfig()->getOptions();

        $this->assertArrayHasKey('selectedEntity', $templateOptions);
        $this->assertEquals(\stdClass::class, $templateOptions['selectedEntity']);
        $this->assertArrayHasKey('query_builder', $templateOptions);
        $this->assertInstanceOf(\Closure::class, $templateOptions['query_builder']);

        $this->repository->expects($this->once())
            ->method('getEntityTemplatesQueryBuilder')
            ->with(
                \stdClass::class,
                $this->tokenAccessor->getOrganization(),
                true,
                true,
                true,
                []
            );

        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $templateOptions['query_builder']($this->repository);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([EmailType::class], QuoteEmailTemplateExtension::getExtendedTypes());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $translatableEntity = $this->getMockBuilder(TranslatableEntityType::class)
            ->onlyMethods(['configureOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        return [
            new PreloadedExtension(
                [
                    EmailType::class => $this->createEmailType(),
                    ContextsSelectType::class => $this->createContextsSelectType(),
                    EmailAddressRecipientsType::class => new EmailAddressRecipientsType($this->configManager),
                    EmailOriginFromType::class => $this->createEmailOriginFromType(),
                    OroRichTextType::class => new OroRichTextType(
                        $this->configManager,
                        new HtmlTagProvider([]),
                        new NullContext(),
                        $htmlTagHelper
                    ),
                    TranslatableEntityType::class => $translatableEntity,
                ],
                [EmailType::class => [$this->extension]]
            ),
            $this->getValidatorExtension()
        ];
    }

    private function createEmailType(): EmailType
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $emailRenderer = $this->createMock(EmailRenderer::class);
        $emailModelBuilderHelper = $this->createMock(EmailModelBuilderHelper::class);

        $authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        return new EmailType(
            $authorizationChecker,
            $this->tokenAccessor,
            $emailRenderer,
            $emailModelBuilderHelper,
            $this->configManager
        );
    }

    private function createContextsSelectType(): ContextsSelectType
    {
        $configManager = $this->createMock(EntityConfigManager::class);
        $translator = $this->createMock(DataCollectorTranslator::class);

        return new ContextsSelectType(
            $this->em,
            $configManager,
            $translator,
            new EventDispatcher(),
            $this->createMock(EntityNameResolver::class),
            $this->featureChecker
        );
    }

    private function createEmailOriginFromType(): EmailOriginFromType
    {
        $relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);
        $emailModelBuilderHelper = $this->createMock(EmailModelBuilderHelper::class);
        $mailboxManager = $this->createMock(MailboxManager::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $emailOriginHelper = $this->createMock(EmailOriginHelper::class);

        $registry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        return new EmailOriginFromType(
            $this->tokenAccessor,
            $relatedEmailsProvider,
            $emailModelBuilderHelper,
            $mailboxManager,
            $registry,
            $emailOriginHelper
        );
    }
}
