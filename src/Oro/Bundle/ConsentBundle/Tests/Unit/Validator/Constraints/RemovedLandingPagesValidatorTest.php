<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPagesValidator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class RemovedLandingPagesValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RemovedLandingPagesValidator */
    private $validator;

    /** @var PageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $pageRepository;

    /** @var RemovedLandingPages */
    private $constraint;

    /** @var ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->pageRepository = $this->createMock(PageRepository::class);
        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->with(Page::class)
            ->willReturn($this->pageRepository);

        $this->validator = new RemovedLandingPagesValidator($doctrineHelper);
        $this->context = $this->createMock(ExecutionContext::class);
        $this->validator->initialize($this->context);

        $this->constraint = new RemovedLandingPages();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->pageRepository);
        unset($this->validator);
        unset($this->context);
        unset($this->constraint);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Incorrect type of the value!
     */
    public function testValidateWithIncorrectType()
    {
        $this->validator->validate('not array', $this->constraint);
    }

    /**
     * @dataProvider validateProvider
     *
     * @param array $value
     * @param array $checkedConsentIds
     * @param array $nonExistentConsentIds
     */
    public function testValidate(array $value, array $checkedConsentIds, array $nonExistentConsentIds)
    {
        $value = new ArrayCollection($value);

        if (empty($checkedConsentIds)) {
            $this->pageRepository
                ->expects($this->never())
                ->method('getNonExistentPageIds');
        } else {
            $this->pageRepository
                ->expects($this->once())
                ->method('getNonExistentPageIds')
                ->with($checkedConsentIds)
                ->willReturn($nonExistentConsentIds);
        }

        if (empty($nonExistentConsentIds)) {
            $this->context
                ->expects($this->never())
                ->method('buildViolation');
        } else {
            $this->context
                ->expects($this->once())
                ->method('buildViolation')
                ->with($this->constraint->message)
                ->willReturn(
                    $this->createMock(ConstraintViolationBuilderInterface::class)
                );
        }

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        $consentAcceptanceWithExistedLandingPage = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 7,
                'landingPage' => $this->getEntity(Page::class, ['id' => 1])
            ]
        );

        $consentAcceptanceWithNonExistentLandingPage = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 8,
                'landingPage' => $this->getEntity(Page::class, ['id' => 2])
            ]
        );

        return [
            "Empty value" => [
                'value' => [],
                'checkedConsentIds' => [],
                'nonExistentConsentIds' => []
            ],
            "Only existed landing pages in value" => [
                'value' => [$consentAcceptanceWithExistedLandingPage],
                'checkedConsentIds' => [1],
                'nonExistentConsentIds' => []
            ],
            "Only not existed landing pages in value" => [
                'value' => [$consentAcceptanceWithNonExistentLandingPage],
                'checkedConsentIds' => [2],
                'nonExistentConsentIds' => [2]
            ]
        ];
    }
}
