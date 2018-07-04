<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ShoppingListBundle\Placeholder\PlaceholderFilter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testUserCanCreateLineItem()
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update')
            ->willReturn(true);

        $placeholderFilter = new PlaceholderFilter($authorizationChecker);
        self::assertTrue($placeholderFilter->userCanCreateLineItem());
    }
}
