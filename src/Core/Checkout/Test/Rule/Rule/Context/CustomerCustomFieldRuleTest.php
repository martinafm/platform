<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Test\Rule\Rule\Context;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\CheckoutRuleScope;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Rule\CustomerCustomFieldRule;
use Shopware\Core\Checkout\Test\Cart\Rule\Helper\CartRuleHelperTrait;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('business-ops')]
class CustomerCustomFieldRuleTest extends TestCase
{
    use CartRuleHelperTrait;

    private const CUSTOM_FIELD_NAME = 'custom_test';

    private CustomerCustomFieldRule $rule;

    private SalesChannelContext $salesChannelContext;

    private MockObject $customer;

    private CheckoutRuleScope $scope;

    protected function setUp(): void
    {
        $this->rule = new CustomerCustomFieldRule();

        $this->salesChannelContext = $this->getMockBuilder(SalesChannelContext::class)->disableOriginalConstructor()->getMock();
        $this->salesChannelContext->method('getContext')->willReturn(Context::createDefaultContext());

        $this->customer = $this->getMockBuilder(CustomerEntity::class)->disableOriginalConstructor()->getMock();
        $this->salesChannelContext->method('getCustomer')->willReturn($this->customer);

        $this->scope = new CheckoutRuleScope($this->salesChannelContext);
    }

    public function testGetName(): void
    {
        static::assertSame('customerCustomField', $this->rule->getName());
    }

    public function testGetConstraints(): void
    {
        $ruleConstraints = $this->rule->getConstraints();

        static::assertArrayHasKey('operator', $ruleConstraints, 'Rule Constraint operator is not defined');
        static::assertArrayHasKey('renderedField', $ruleConstraints, 'Rule Constraint renderedField is not defined');
        static::assertArrayHasKey('renderedFieldValue', $ruleConstraints, 'Rule Constraint renderedFieldValue is not defined');
        static::assertArrayHasKey('selectedField', $ruleConstraints, 'Rule Constraint selectedField is not defined');
        static::assertArrayHasKey('selectedFieldSet', $ruleConstraints, 'Rule Constraint selectedFieldSet is not defined');
    }

    public function testBooleanCustomFieldFalseWithNoValue(): void
    {
        $this->setupRule(false, 'bool');
        $this->setCustomerCustomFields([]);
        static::assertTrue($this->rule->match($this->scope));
    }

    public function testBooleanCustomFieldFalse(): void
    {
        $this->setupRule(false, 'bool');
        $this->setCustomerCustomFields([
            self::CUSTOM_FIELD_NAME => false,
        ]);
        static::assertTrue($this->rule->match($this->scope));
    }

    public function testBooleanCustomFieldTrue(): void
    {
        $this->setupRule(true, 'bool');
        $this->setCustomerCustomFields([
            self::CUSTOM_FIELD_NAME => true,
        ]);
        static::assertTrue($this->rule->match($this->scope));
    }

    /**
     * @dataProvider getStringRuleValueWhichShouldBeConsideredAsTrueProvider
     */
    public function testBooleanCustomFieldTrueWhenIsRuleIsSetupAsString(string $value): void
    {
        $this->setupRule($value, 'bool');
        $this->setCustomerCustomFields([
            self::CUSTOM_FIELD_NAME => true,
        ]);
        static::assertTrue($this->rule->match($this->scope));
    }

    /**
     * @dataProvider getStringRuleValueWhichShouldBeConsideredAsFalseProvider
     */
    public function testBooleanCustomFieldFalseWhenIsRuleIsSetupAsString(string $value): void
    {
        $this->setupRule($value, 'bool');
        $this->setCustomerCustomFields([
            self::CUSTOM_FIELD_NAME => false,
        ]);
        static::assertTrue($this->rule->match($this->scope));
    }

    /**
     * @dataProvider getStringRuleValueWhichShouldBeConsideredAsTrueProvider
     */
    public function testBooleanCustomFieldInvalidAsString(string $value): void
    {
        $this->setupRule($value, 'bool');
        $this->setCustomerCustomFields([
            self::CUSTOM_FIELD_NAME => false,
        ]);
        static::assertFalse($this->rule->match($this->scope));
    }

    public function testBooleanCustomFieldNull(): void
    {
        $this->setupRule(null, 'bool');
        $this->setCustomerCustomFields([
            self::CUSTOM_FIELD_NAME => false,
        ]);
        static::assertTrue($this->rule->match($this->scope));
    }

    public function testTextCustomFieldUnequalOperator(): void
    {
        // Case: the rule checks for some text but the line item custom field value is null
        // 'testValue' != null -> true
        $this->setupRule('testValue', 'text');
        $this->rule->assign(
            [
                'operator' => Rule::OPERATOR_NEQ,
            ]
        );
        $this->setCustomerCustomFields([self::CUSTOM_FIELD_NAME => null]);
        static::assertTrue($this->rule->match($this->scope));
    }

    public function testBooleanCustomFieldInvalid(): void
    {
        $this->setupRule(false, 'bool');
        $this->setCustomerCustomFields([self::CUSTOM_FIELD_NAME => true]);
        static::assertFalse($this->rule->match($this->scope));
    }

    public function testStringCustomField(): void
    {
        $this->setupRule('my_test_value', 'string');
        $this->setCustomerCustomFields([self::CUSTOM_FIELD_NAME => 'my_test_value']);
        static::assertTrue($this->rule->match($this->scope));
    }

    public function testStringCustomFieldInvalid(): void
    {
        $this->setupRule('my_test_value', 'string');
        $this->setCustomerCustomFields([self::CUSTOM_FIELD_NAME => 'my_invalid_value']);
        static::assertFalse($this->rule->match($this->scope));
    }

    /**
     * @dataProvider customFieldCheckoutScopeProvider
     *
     * @param bool|string|null $customFieldValue
     * @param bool|string|null $customFieldValueInCustomer
     */
    public function testCustomFieldCheckoutScope(
        $customFieldValue,
        string $type,
        $customFieldValueInCustomer,
        bool $result
    ): void {
        $this->setupRule($customFieldValue, $type);
        $this->setCustomerCustomFields([self::CUSTOM_FIELD_NAME => $customFieldValueInCustomer]);
        static::assertSame($result, $this->rule->match($this->scope));
    }

    public static function customFieldCheckoutScopeProvider(): array
    {
        return [
            'testBooleanCustomFieldFalse' => [false, 'bool', false, true],
            'testBooleanCustomFieldNull' => [null, 'bool', false, true],
            'testBooleanCustomFieldInvalid' => [false, 'bool', true, false],
            'testStringCustomField' => ['my_test_value', 'string', 'my_test_value', true],
            'testStringCustomFieldInvalid' => ['my_test_value', 'string', 'my_invalid_value', false],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function getStringRuleValueWhichShouldBeConsideredAsTrueProvider(): array
    {
        return [
            ['yes'],
            ['True'],
            ['1'],
            ['true'],
            ['yes '],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function getStringRuleValueWhichShouldBeConsideredAsFalseProvider(): array
    {
        return [
            ['no'],
            ['False'],
            ['0'],
            ['false'],
            ['no '],
            ['some string'],
        ];
    }

    private function setCustomerCustomFields(array $customFields = []): void
    {
        $this->customer->method('getCustomFields')->willReturn($customFields);
    }

    /**
     * @param bool|string|null $customFieldValue
     */
    private function setupRule($customFieldValue, string $type): void
    {
        $this->rule->assign(
            [
                'operator' => Rule::OPERATOR_EQ,
                'renderedField' => [
                    'type' => $type,
                    'name' => self::CUSTOM_FIELD_NAME,
                ],
                'renderedFieldValue' => $customFieldValue,
            ]
        );
    }
}
