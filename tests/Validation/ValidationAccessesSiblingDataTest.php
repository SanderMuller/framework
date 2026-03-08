<?php

namespace Illuminate\Tests\Validation;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Concerns\AccessesSiblingData;
use PHPUnit\Framework\TestCase;

class ValidationAccessesSiblingDataTest extends TestCase
{
    public function testSiblingAttributeWithNestedData()
    {
        $rule = new AccessesSiblingDataTestRule;
        $rule->setData(['items' => [['price' => 100, 'currency' => 'EUR']]]);

        $this->assertEquals('items.0.currency', $rule->exposeSiblingAttribute('items.0.price', 'currency'));
    }

    public function testSiblingAttributeWithTopLevelData()
    {
        $rule = new AccessesSiblingDataTestRule;
        $rule->setData(['price' => 100, 'currency' => 'EUR']);

        $this->assertEquals('currency', $rule->exposeSiblingAttribute('price', 'currency'));
    }

    public function testSiblingAttributeWithDeeplyNestedData()
    {
        $rule = new AccessesSiblingDataTestRule;
        $rule->setData([]);

        $this->assertEquals('orders.0.items.2.currency', $rule->exposeSiblingAttribute('orders.0.items.2.price', 'currency'));
    }

    public function testSiblingValueReturnsCorrectValue()
    {
        $rule = new AccessesSiblingDataTestRule;
        $rule->setData([
            'items' => [
                ['price' => 100, 'currency' => 'EUR'],
                ['price' => 200, 'currency' => 'USD'],
            ],
        ]);

        $this->assertEquals('EUR', $rule->exposeSiblingValue('items.0.price', 'currency'));
        $this->assertEquals('USD', $rule->exposeSiblingValue('items.1.price', 'currency'));
    }

    public function testSiblingValueReturnsNullForMissingSibling()
    {
        $rule = new AccessesSiblingDataTestRule;
        $rule->setData([
            'items' => [
                ['price' => 100],
            ],
        ]);

        $this->assertNull($rule->exposeSiblingValue('items.0.price', 'currency'));
    }

    public function testSiblingValueWithTopLevelData()
    {
        $rule = new AccessesSiblingDataTestRule;
        $rule->setData(['price' => 100, 'currency' => 'EUR']);

        $this->assertEquals('EUR', $rule->exposeSiblingValue('price', 'currency'));
    }

    public function testSiblingValueWithDottedSiblingName()
    {
        $rule = new AccessesSiblingDataTestRule;
        $rule->setData([
            'interactions' => [
                ['duration' => 5, 'action' => ['shouldPauseVideo' => true]],
            ],
        ]);

        $this->assertEquals('interactions.0.action.shouldPauseVideo', $rule->exposeSiblingAttribute('interactions.0.duration', 'action.shouldPauseVideo'));
        $this->assertTrue($rule->exposeSiblingValue('interactions.0.duration', 'action.shouldPauseVideo'));
    }

    public function testTraitWorksWithValidatorIntegration()
    {
        $translator = new Translator(new ArrayLoader, 'en');

        $validator = new \Illuminate\Validation\Validator(
            $translator,
            [
                'items' => [
                    ['price' => 100, 'currency' => 'EUR'],
                    ['price' => -5, 'currency' => 'USD'],
                ],
            ],
            [
                'items.*.price' => [new PriceMatchesCurrencyRule],
            ],
        );

        $this->assertTrue($validator->passes());
    }

    public function testTraitWorksWithValidatorIntegrationFailure()
    {
        $translator = new Translator(new ArrayLoader, 'en');

        $validator = new \Illuminate\Validation\Validator(
            $translator,
            [
                'items' => [
                    ['price' => 100, 'currency' => 'INVALID'],
                ],
            ],
            [
                'items.*.price' => [new PriceMatchesCurrencyRule],
            ],
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('items.0.price', $validator->errors()->toArray());
    }
}

class AccessesSiblingDataTestRule implements DataAwareRule, ValidationRule
{
    use AccessesSiblingData;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
    }

    public function exposeSiblingAttribute(string $attribute, string $sibling): string
    {
        return $this->siblingAttribute($attribute, $sibling);
    }

    public function exposeSiblingValue(string $attribute, string $sibling): mixed
    {
        return $this->siblingValue($attribute, $sibling);
    }
}

class PriceMatchesCurrencyRule implements DataAwareRule, ValidationRule
{
    use AccessesSiblingData;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $currency = $this->siblingValue($attribute, 'currency');

        if (! in_array($currency, ['EUR', 'USD', 'GBP'])) {
            $fail('The :attribute has an invalid currency.');
        }
    }
}
