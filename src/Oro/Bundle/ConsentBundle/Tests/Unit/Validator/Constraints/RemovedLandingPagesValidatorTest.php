<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPagesValidator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RemovedLandingPagesValidatorTest extends ConstraintValidatorTestCase
{
    /** @var PageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $pageRepository;

    protected function setUp(): void
    {
        $this->pageRepository = $this->createMock(PageRepository::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(Page::class)
            ->willReturn($this->pageRepository);

        return new RemovedLandingPagesValidator($doctrineHelper);
    }

    public function testValidateWithIncorrectType()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Incorrect type of the value!');

        $constraint = new RemovedLandingPages();
        $this->validator->validate('not array', $constraint);
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(array $value, array $checkedConsentIds, array $nonExistentConsentIds)
    {
        $value = new ArrayCollection($value);

        if (empty($checkedConsentIds)) {
            $this->pageRepository->expects($this->never())
                ->method('getNonExistentPageIds');
        } else {
            $this->pageRepository->expects($this->once())
                ->method('getNonExistentPageIds')
                ->with($checkedConsentIds)
                ->willReturn($nonExistentConsentIds);
        }

        $constraint = new RemovedLandingPages();
        $this->validator->validate($value, $constraint);

        if (empty($nonExistentConsentIds)) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        $page1 = new Page();
        ReflectionUtil::setId($page1, 1);
        $consentAcceptanceWithExistedLandingPage = new ConsentAcceptance();
        ReflectionUtil::setId($consentAcceptanceWithExistedLandingPage, 7);
        $consentAcceptanceWithExistedLandingPage->setLandingPage($page1);

        $page2 = new Page();
        ReflectionUtil::setId($page2, 2);
        $consentAcceptanceWithNonExistentLandingPage = new ConsentAcceptance();
        ReflectionUtil::setId($consentAcceptanceWithNonExistentLandingPage, 7);
        $consentAcceptanceWithNonExistentLandingPage->setLandingPage($page2);

        return [
            'Empty value' => [
                'value' => [],
                'checkedConsentIds' => [],
                'nonExistentConsentIds' => []
            ],
            'Only existed landing pages in value' => [
                'value' => [$consentAcceptanceWithExistedLandingPage],
                'checkedConsentIds' => [1],
                'nonExistentConsentIds' => []
            ],
            'Only not existed landing pages in value' => [
                'value' => [$consentAcceptanceWithNonExistentLandingPage],
                'checkedConsentIds' => [2],
                'nonExistentConsentIds' => [2]
            ]
        ];
    }
}
