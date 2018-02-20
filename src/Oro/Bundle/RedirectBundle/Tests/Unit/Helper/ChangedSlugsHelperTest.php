<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\DTO\SlugUrl;
use Oro\Bundle\RedirectBundle\Generator\SlugEntityGenerator;
use Oro\Bundle\RedirectBundle\Generator\SlugUrlDiffer;
use Oro\Bundle\RedirectBundle\Helper\ChangedSlugsHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ChangedSlugsHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FormFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var SlugEntityGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $slugGenerator;

    /**
     * @var SlugUrlDiffer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $slugUrlDiffer;

    /**
     * @var ChangedSlugsHelper
     */
    private $helper;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->slugGenerator = $this->getMockBuilder(SlugEntityGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->slugUrlDiffer = $this->getMockBuilder(SlugUrlDiffer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->helper = new ChangedSlugsHelper(
            $this->formFactory,
            $requestStack,
            $this->slugGenerator,
            $this->slugUrlDiffer
        );
    }

    public function testGetChangedSlugsJsonResponse()
    {
        $formType = 'FormType';
        /** @var SluggableInterface $entity */
        $entity = $this->createMock(SluggableInterface::class);

        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $formData = $this->createMock(SluggableInterface::class);
        $form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($formData);

        $this->formFactory
            ->expects($this->once())
            ->method('create')
            ->with($formType, $entity)
            ->willReturn($form);

        /** @var Localization $defaultLocalization */
        $defaultLocalization = $this->getEntity(Localization::class, ['id' => 0]);
        /** @var Localization $defaultLocalization */
        $englishLocalization = $this->getEntity(Localization::class, ['id' => 1]);

        $beforeDefaultSlugUrl = new SlugUrl('beforeDefaultValue', $defaultLocalization);
        $beforeEnglishSlugUrl = new SlugUrl('beforeEnglishValue', $englishLocalization);

        $afterDefaultSlugUrl = new SlugUrl('afterDefaultValue', $defaultLocalization);
        $afterEnglishSlugUrl = new SlugUrl('afterEnglishValue', $englishLocalization);

        $this->slugGenerator
            ->expects($this->any())
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

        $this->slugUrlDiffer
            ->expects($this->once())
            ->method('getSlugUrlsChanges')
            ->willReturn($diffData);

        $this->assertEquals($diffData, $this->helper->getChangedSlugsData($entity, $formType));
    }
}
