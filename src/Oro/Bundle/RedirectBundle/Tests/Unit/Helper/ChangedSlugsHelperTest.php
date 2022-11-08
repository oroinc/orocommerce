<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Bundle\RedirectBundle\Helper\ChangedSlugsHelper;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ChangedSlugsHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FormFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var Request */
    private $request;

    /** @var SlugEntityGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $slugGenerator;

    /** @var SlugUrlDiffer|\PHPUnit\Framework\MockObject\MockObject */
    private $slugUrlDiffer;

    /** @var DraftHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $draftHelper;

    /** @var ChangedSlugsHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->slugGenerator = $this->createMock(SlugEntityGenerator::class);
        $this->slugUrlDiffer = $this->createMock(SlugUrlDiffer::class);
        $this->draftHelper = $this->createMock(DraftHelper::class);
        $this->request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->helper = new ChangedSlugsHelper(
            $this->formFactory,
            $requestStack,
            $this->slugGenerator,
            $this->slugUrlDiffer,
            $this->draftHelper
        );
    }

    public function testGetChangedSlugsJsonResponseWithIsSaveAsDraftAction(): void
    {
        $this->draftHelper->expects($this->once())
            ->method('isSaveAsDraftAction')
            ->willReturn(true);

        $formType = 'FormType';
        $entity = new Page();

        $this->assertEmpty($this->helper->getChangedSlugsData($entity, $formType));
    }

    public function testGetChangedSlugsJsonResponseWithIsDraft(): void
    {
        $formType = 'FormType';
        $entity = new Page();
        $entity->setDraftUuid(UUIDGenerator::v4());

        $this->assertEmpty($this->helper->getChangedSlugsData($entity, $formType));
    }

    public function testGetChangedSlugsJsonResponse()
    {
        $formType = 'FormType';
        $entity = $this->createMock(SluggableInterface::class);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);

        $formData = $this->createMock(SluggableInterface::class);
        $form->expects($this->once())
            ->method('getData')
            ->willReturn($formData);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with($formType, $entity)
            ->willReturn($form);

        $defaultLocalization = $this->getEntity(Localization::class, ['id' => 0]);
        $englishLocalization = $this->getEntity(Localization::class, ['id' => 1]);

        $beforeDefaultSlugUrl = new SlugUrl('beforeDefaultValue', $defaultLocalization);
        $beforeEnglishSlugUrl = new SlugUrl('beforeEnglishValue', $englishLocalization);

        $afterDefaultSlugUrl = new SlugUrl('afterDefaultValue', $defaultLocalization);
        $afterEnglishSlugUrl = new SlugUrl('afterEnglishValue', $englishLocalization);

        $this->slugGenerator->expects($this->any())
            ->method('prepareSlugUrls')
            ->willReturnMap([
                [
                    $entity,
                    new ArrayCollection([
                        $defaultLocalization->getId() => $beforeDefaultSlugUrl,
                        $englishLocalization->getId() => $beforeEnglishSlugUrl
                    ])
                ],
                [
                    $formData,
                    new ArrayCollection([
                        $defaultLocalization->getId() => $afterDefaultSlugUrl,
                        $englishLocalization->getId() => $afterEnglishSlugUrl
                    ])
                ]
            ]);

        $diffData = [
            ['Default' => ['before' => $beforeDefaultSlugUrl->getUrl(), 'after' => $afterDefaultSlugUrl->getUrl()]],
            ['English' => ['before' => $beforeEnglishSlugUrl->getUrl(), 'after' => $afterEnglishSlugUrl->getUrl()]]
        ];

        $this->slugUrlDiffer->expects($this->once())
            ->method('getSlugUrlsChanges')
            ->willReturn($diffData);

        $this->assertEquals($diffData, $this->helper->getChangedSlugsData($entity, $formType));
    }
}
