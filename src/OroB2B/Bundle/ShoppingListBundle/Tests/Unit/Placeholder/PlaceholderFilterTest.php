<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ShoppingListBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testUserCanCreateLineItem()
    {
        /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject $securityFacade */
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('orob2b_shopping_list_line_item_frontend_add');

        $placeholderFilter = new PlaceholderFilter($securityFacade);
        $placeholderFilter->userCanCreateLineItem();
    }
}
