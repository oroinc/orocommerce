<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
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
use Oro\Bundle\SecurityBundle\Tests\Unit\Authorization\FakeAuthorizationChecker;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\NullContext;
use Symfony\Component\EventDispatcher\EventDispatcher;
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

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->repository = $this->createMock(EmailTemplateRepository::class);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor
            ->method('getOrganization')
            ->willReturn(new Organization());

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new QuoteEmailTemplateExtension($this->tokenAccessor, $this->featureChecker);

        parent::setUp();
    }

    /**
     * @dataProvider buildFormDataProvider
     *
     * @param bool $isFeatureEnabled
     * @param array $excludeNames
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

    /**
     * @return array
     */
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

    public function testGetExtendedType(): void
    {
        $this->assertEquals(EmailType::class, $this->extension->getExtendedType());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var TranslatableEntityType $translatableEntity */
        $translatableEntity = $this->getMockBuilder(TranslatableEntityType::class)
            ->setMethods(['configureOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

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
                        new NullContext()
                    ),
                    TranslatableEntityType::class => $translatableEntity,
                ],
                [$this->extension->getExtendedType() => [$this->extension]]
            ),
            $this->getValidatorExtension()
        ];
    }

    /**
     * @return EmailType
     */
    private function createEmailType(): EmailType
    {
        /** @var EmailRenderer $emailRenderer */
        $emailRenderer = $this->createMock(EmailRenderer::class);

        /** @var EmailModelBuilderHelper $emailModelBuilderHelper */
        $emailModelBuilderHelper = $this->createMock(EmailModelBuilderHelper::class);

        return new EmailType(
            new FakeAuthorizationChecker(),
            $this->tokenAccessor,
            $emailRenderer,
            $emailModelBuilderHelper,
            $this->configManager
        );
    }

    /**
     * @return ContextsSelectType
     */
    private function createContextsSelectType(): ContextsSelectType
    {
        /** @var EntityConfigManager $configManager */
        $configManager = $this->createMock(EntityConfigManager::class);

        /** @var DataCollectorTranslator $translator */
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

    /**
     * @return EmailOriginFromType
     */
    private function createEmailOriginFromType(): EmailOriginFromType
    {
        /** @var RelatedEmailsProvider $relatedEmailsProvider */
        $relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);

        /** @var EmailModelBuilderHelper $emailModelBuilderHelper */
        $emailModelBuilderHelper = $this->createMock(EmailModelBuilderHelper::class);

        /** @var MailboxManager $mailboxManager */
        $mailboxManager = $this->createMock(MailboxManager::class);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManager')
            ->willReturn($this->em);

        /** @var EmailOriginHelper $emailOriginHelper */
        $emailOriginHelper = $this->createMock(EmailOriginHelper::class);

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
