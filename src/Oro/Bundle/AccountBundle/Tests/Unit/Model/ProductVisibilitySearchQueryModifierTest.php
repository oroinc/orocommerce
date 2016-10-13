<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\AccountBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison as SearchComparison;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AccountIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\PlaceholderProvider;

class ProductVisibilitySearchQueryModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var PlaceholderProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $placeholderProvider;

    /**
     * @var ProductVisibilitySearchQueryModifier
     */
    protected $modifier;

    protected function setUp()
    {
        $this->tokenStorage = $this
            ->getMockBuilder(TokenStorageInterface::class)
            ->getMock();

        $this->placeholderProvider = $this
            ->getMockBuilder(PlaceholderProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->modifier = new ProductVisibilitySearchQueryModifier(
            $this->tokenStorage,
            $this->placeholderProvider
        );
    }

    public function testModify()
    {
        $this->placeholderProvider
            ->expects($this->once())
            ->method('getPlaceholderFieldName')
            ->with(
                Product::class,
                ProductVisibilityIndexer::FIELD_VISIBILITY_ACCOUNT,
                [
                    AccountIdPlaceholder::NAME => 1
                ]
            )
            ->willReturn('visibility_account_1');

        $account = new Account();
        $reflection = new \ReflectionProperty(Account::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($account, 1);

        $accountUser = new AccountUser();
        $accountUser->setAccount($account);

        $token = $this
            ->getMockBuilder(TokenInterface::class)
            ->getMock();

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $query = new Query();
        $this->modifier->modify($query);

        $hidden = BaseVisibilityResolved::VISIBILITY_HIDDEN;
        $visible = BaseVisibilityResolved::VISIBILITY_VISIBLE;

        $expected = new CompositeExpression(
            CompositeExpression::TYPE_OR,
            [
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($visible)),
                        new SearchComparison(
                            'integer.visibility_account_1',
                            SearchComparison::NOT_EXISTS,
                            new Value(null)
                        ),
                    ]
                ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('integer.is_visible_by_default', Comparison::EQ, new Value($hidden)),
                        new Comparison('integer.visibility_account_1', Comparison::EQ, new Value($visible)),
                    ]
                ),
            ]
        );

        $this->assertEquals($expected, $query->getCriteria()->getWhereExpression());
    }

    /**
     * @return array
     */
    public function wrongAccountUserProvider()
    {
        return [
            [null],
            [new \stdClass()]
        ];
    }

    /**
     * @dataProvider wrongAccountUserProvider
     *
     * @param mixed $accountUser
     */
    public function testModifyForAnonymous($accountUser)
    {
        $this->placeholderProvider
            ->expects($this->never())
            ->method('getPlaceholderFieldName');

        $token = $this
            ->getMockBuilder(TokenInterface::class)
            ->getMock();

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($accountUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $expected = new Comparison(
            'integer.visibility_anonymous',
            Comparison::EQ,
            new Value(ProductVisibilityIndexer::ACCOUNT_VISIBILITY_VALUE)
        );

        $query = new Query();
        $this->modifier->modify($query);

        $this->assertEquals($expected, $query->getCriteria()->getWhereExpression());
    }
}
