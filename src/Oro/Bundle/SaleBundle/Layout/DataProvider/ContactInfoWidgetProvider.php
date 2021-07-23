<?php

namespace Oro\Bundle\SaleBundle\Layout\DataProvider;

use Oro\Bundle\SaleBundle\Provider\ContactInfoProvider;
use Oro\Bundle\SaleBundle\Provider\ContactInfoProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class ContactInfoWidgetProvider
{
    const WIDGET_VIEW_BLANK = '_sales_menu_blank_widget';
    const WIDGET_VIEW_TEXT = '_sales_menu_text_info_widget';
    const WIDGET_VIEW_USER = '_sales_menu_user_info_widget';

    /**
     * @var TokenAccessorInterface
     */
    protected $tokenAccessor;

    /**
     * @var ContactInfoProvider
     */
    protected $contactInfoProvider;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        ContactInfoProviderInterface $contactInfoProvider
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->contactInfoProvider = $contactInfoProvider;
    }

    /**
     * @return array
     */
    public function getContactInfoBlock()
    {
        $currentUser = $this->tokenAccessor->getUser();
        $contactInfo = $this->contactInfoProvider->getContactInfo($currentUser);

        $widget = self::WIDGET_VIEW_BLANK;
        if (!$contactInfo->isEmpty()) {
            if ($contactInfo->getManualText()) {
                $widget = self::WIDGET_VIEW_TEXT;
            } else {
                $widget = self::WIDGET_VIEW_USER;
            }
        }

        $block = [
            'widget' => $widget,
            'attributes' => [
                'contactInfo' => $contactInfo
            ]
        ];

        return $block;
    }
}
