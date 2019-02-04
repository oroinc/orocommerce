<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Form\DataTransformer\CustomerConsentsTransformer;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerConsentsTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CustomerConsentsTransformer */
    private $dataTransformer;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $consentAcceptanceProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->consentAcceptanceProvider = $this->createMock(
            ConsentAcceptanceProvider::class
        );

        $this->dataTransformer = new CustomerConsentsTransformer(
            $this->doctrineHelper,
            $this->consentAcceptanceProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset($this->doctrineHelper);
        unset($this->consentAcceptanceProvider);
        unset($this->dataTransformer);
    }

    /**
     * @dataProvider transformProvider
     *
     * @param mixed  $consentAcceptances
     * @param string $expectedTransformedValue
     */
    public function testTransform($consentAcceptances, $expectedTransformedValue)
    {
        $transformerValue = $this->dataTransformer->transform($consentAcceptances);
        $this->assertSame(
            $expectedTransformedValue,
            $transformerValue
        );
    }

    /**
     * @return array
     */
    public function transformProvider()
    {
        return [
            'Incorrect data on transformation' => [
                'consentAcceptances' => '',
                'expectedTransformedValue' => '[]'
            ],
            'Empty data on transformation' => [
                'consentAcceptances' => [],
                'expectedTransformedValue' => '[]'
            ],
            'Valid data on transformation' => [
                'consentAcceptances' => [
                    $this->getEntity(
                        ConsentAcceptance::class,
                        [
                            'consent' => $this->getEntity(Consent::class, ['id' => 1]),
                            'landingPage' => $this->getEntity(Page::class, ['id' => 1])
                        ]
                    ),
                    $this->getEntity(
                        ConsentAcceptance::class,
                        [
                            'consent' => $this->getEntity(Consent::class, ['id' => 2]),
                            'landingPage' => $this->getEntity(Page::class, ['id' => 2])
                        ]
                    ),
                ],
                'expectedTransformedValue' => '[{"consentId":1,"cmsPageId":1},{"consentId":2,"cmsPageId":2}]'
            ]
        ];
    }

    /**
     * @dataProvider reverseTransformInvalidDataProvider
     *
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     *
     * @param string $invalidData
     * @param string $exceptionMessage
     */
    public function testReverseTransformInvalidData($invalidData, $exceptionMessage)
    {
        $this->expectExceptionMessage($exceptionMessage);

        $this->dataTransformer->reverseTransform($invalidData);
    }

    /**
     * @return array
     */
    public function reverseTransformInvalidDataProvider()
    {
        return [
            'Malformed json' => [
                'invalidData' => '1[]',
                'exceptionMessage' => 'Expected an array after decoding a string.'
            ],
            'Incorrect data format' => [
                'invalidData' => '[{}]',
                'exceptionMessage' => 'Missing data by required key(s) in the encoded array item.'
            ],
            'Missing data by key "consentId"' => [
                'invalidData' => '[{"cmsPageId":1}]',
                'exceptionMessage' => 'Missing data by required key(s) in the encoded array item.'
            ]
        ];
    }

    /**
     * @dataProvider reverseTransformProvider
     *
     * @param string $encodedConsentAcceptanceData
     * @param array  $consentAcceptancesReturnedByProvider
     * @param array  $consentAcceptancesOnCreate
     * @param array  $expectedReverseTransformedValue
     */
    public function testReverseTransform(
        string $encodedConsentAcceptanceData,
        array $consentAcceptancesReturnedByProvider,
        array $consentAcceptancesOnCreate,
        array $expectedReverseTransformedValue
    ) {
        $dataItemCount = count($consentAcceptancesReturnedByProvider + $consentAcceptancesOnCreate);

        if ($dataItemCount === 0) {
            $this->consentAcceptanceProvider
                ->expects($this->never())
                ->method('getCustomerConsentAcceptanceByConsentId');
        } else {
            $this->consentAcceptanceProvider
                ->expects($this->exactly($dataItemCount))
                ->method('getCustomerConsentAcceptanceByConsentId')
                ->willReturnCallback(
                    function (int $id) use ($consentAcceptancesReturnedByProvider) {
                        return $consentAcceptancesReturnedByProvider[$id] ?? null;
                    }
                );
        }

        if (empty($consentAcceptancesOnCreate)) {
            $this->doctrineHelper
                ->expects($this->never())
                ->method('createEntityInstance');
            $this->doctrineHelper
                ->expects($this->never())
                ->method('getEntityReference');
        } else {
            $this->doctrineHelper
                ->expects($this->exactly(count($consentAcceptancesOnCreate)))
                ->method('createEntityInstance')
                ->willReturnCallback(function ($class) {
                    return $this->getEntity($class);
                });

            $this->doctrineHelper
                ->expects($this->any())
                ->method('getEntityReference')
                ->willReturnCallback(function ($class, $id) {
                    return $this->getEntity($class, ['id' => $id]);
                });
        }

        $consentAcceptanceData = $this->dataTransformer->reverseTransform($encodedConsentAcceptanceData);
        $this->assertEquals($consentAcceptanceData, new ArrayCollection($expectedReverseTransformedValue));
    }

    /**
     * @return array
     */
    public function reverseTransformProvider()
    {
        $consentAcceptanceReturnedByProvider = $this->getEntity(
            ConsentAcceptance::class,
            [
                'id' => 1,
                'consent' => $this->getEntity(Consent::class, ['id' => 1]),
                'landingPage' => $this->getEntity(Page::class, ['id' => 1])
            ]
        );

        $createdConsentAcceptance1 = $this->getEntity(
            ConsentAcceptance::class,
            [
                'consent' => $this->getEntity(Consent::class, ['id' => 1]),
                'landingPage' => $this->getEntity(Page::class, ['id' => 1])
            ]
        );

        $createdConsentAcceptance2 = $this->getEntity(
            ConsentAcceptance::class,
            [
                'consent' => $this->getEntity(Consent::class, ['id' => 2]),
                'landingPage' => $this->getEntity(Page::class, ['id' => 2])
            ]
        );

        return [
            'Empty data on reverse transform' => [
                'encodedConsentData' => '[]',
                'consentAcceptancesReturnedByProvider' => [],
                'consentAcceptancesOnCreate' => [],
                'expectedReverseTransformedValue' => []
            ],
            'Found existing consentAcceptance' => [
                'encodedConsentData' => '[{"consentId":1,"cmsPageId":1}]',
                'consentAcceptancesReturnedByProvider' => [
                    1 => $consentAcceptanceReturnedByProvider
                ],
                'consentAcceptancesOnCreate' => [],
                'expectedReverseTransformedValue' => [$consentAcceptanceReturnedByProvider]
            ],
            'New consentAcceptance' => [
                'encodedConsentData' => '[{"consentId":1,"cmsPageId":1}]',
                'consentAcceptancesReturnedByProvider' => [],
                'consentAcceptancesOnCreate' => [1],
                'expectedReverseTransformedValue' => [$createdConsentAcceptance1]
            ],
            'Found existing consentAcceptance and new consentAcceptance' => [
                'encodedConsentData' => '[{"consentId":1,"cmsPageId":1},{"consentId":2,"cmsPageId":2}]',
                'consentAcceptancesReturnedByProvider' => [
                    1 => $consentAcceptanceReturnedByProvider
                ],
                'consentAcceptancesOnCreate' => [2],
                'expectedReverseTransformedValue' => [$consentAcceptanceReturnedByProvider, $createdConsentAcceptance2]
            ],
        ];
    }
}
