<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Extension;

use Doctrine\ORM\QueryBuilder;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Extension\QuoteEmailTemplateExtension;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\Form\EmailTypeStub;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;

class QuoteEmailTemplateExtensionTest extends FormIntegrationTestCase
{
    /** @var EmailTemplateRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenAccessor;

    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var QuoteEmailTemplateExtension */
    private $extension;

    protected function setUp()
    {
        $this->repository = $this->createMock(EmailTemplateRepository::class);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects($this->any())
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
    public function testBuildForm(bool $isFeatureEnabled, array $excludeNames)
    {
        $data = new Email();
        $data->setEntityClass(Quote::class);

        $form = $this->factory->create('oro_email_email');
        $form->setData($data);

        $template = $form->get('template');
        $templateOptions = $template->getConfig()->getOptions();

        $this->assertArrayHasKey('selectedEntity', $templateOptions);
        $this->assertEquals(Quote::class, $templateOptions['selectedEntity']);
        $this->assertArrayHasKey('query_builder', $templateOptions);
        $this->assertInstanceOf(\Closure::class, $templateOptions['query_builder']);

        $qb = $this->createMock(QueryBuilder::class);

        $this->repository->expects($this->once())
            ->method('getTemplatesQueryBuilder')
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

    public function testBuildFormForUnsupportedClass()
    {
        $data = new Email();
        $data->setEntityClass(\stdClass::class);

        $form = $this->factory->create('oro_email_email');
        $form->setData($data);

        $template = $form->get('template');
        $templateOptions = $template->getConfig()->getOptions();

        $this->assertArrayHasKey('selectedEntity', $templateOptions);
        $this->assertNull($templateOptions['selectedEntity']);
        $this->assertArrayHasKey('query_builder', $templateOptions);
        $this->assertNull($templateOptions['query_builder']);
    }

    public function testGetExtendedType(): void
    {
        $this->assertEquals('oro_email_email', $this->extension->getExtendedType());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $emailType = new EmailTypeStub();
        $emailTemplateSelectType = new EmailTemplateSelectType();

        /** @var TranslatableEntityType|\PHPUnit_Framework_MockObject_MockObject $translatableEntity */
        $translatableEntity = $this->getMockBuilder(TranslatableEntityType::class)
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    $emailType->getName() => $emailType,
                    $emailTemplateSelectType->getName() => $emailTemplateSelectType,
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'translatable_entity' => $translatableEntity,
                ],
                [$this->extension->getExtendedType() => [$this->extension]]
            )
        ];
    }
}
