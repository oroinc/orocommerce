<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\SaleBundle\Layout\DataProvider\ContactInfoWidgetProvider;
use Oro\Bundle\SaleBundle\Model\ContactInfo;
use Oro\Bundle\SaleBundle\Provider\ContactInfoProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class ContactInfoWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContactInfoWidgetProvider
     */
    private $widgetProvider;

    /**
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenAccessor;

    /**
     * @var ContactInfoProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactInfoProvider;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->contactInfoProvider = $this->createMock(ContactInfoProviderInterface::class);
        $this->widgetProvider = new ContactInfoWidgetProvider(
            $this->tokenAccessor,
            $this->contactInfoProvider
        );
    }

    /**
     * @dataProvider contactInfoBlockDataProvider
     */
    public function testGetContactInfoBlock(ContactInfo $contactInfo, array $expectedResult)
    {
        $this->contactInfoProvider
            ->method('getContactInfo')
            ->willReturn($contactInfo);

        $result = $this->widgetProvider->getContactInfoBlock();
        static::assertEquals($expectedResult, $result);
    }

    public function contactInfoBlockDataProvider()
    {
        $contactInfo = new ContactInfo();
        $contactInfo->setName('User name');
        $contactInfoBlank = new ContactInfo();
        $contactInfoWithText = new ContactInfo();
        $contactInfoWithText->setManualText('test text');

        return [
            'blank_widget' => [
                'contactInfo' => $contactInfoBlank,
                'expectedResult' => [
                    'widget' => '_sales_menu_blank_widget',
                    'attributes' => [
                        'contactInfo' => $contactInfoBlank
                    ],
                ]
            ],
            'text_widget' => [
                'contactInfo' => $contactInfoWithText,
                'expectedResult' => [
                    'widget' => '_sales_menu_text_info_widget',
                    'attributes' => [
                        'contactInfo' => $contactInfoWithText
                    ],
                ]
            ],
            'user_widget' => [
                'contactInfo' => $contactInfo,
                'expectedResult' => [
                    'widget' => '_sales_menu_user_info_widget',
                    'attributes' => [
                        'contactInfo' => $contactInfo
                    ],
                ]
            ],
        ];
    }
}
