<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ImplicitRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\Concerns\HasEmbeddedRules;
use Illuminate\Validation\Rules\Concerns\HasFieldModifiers;
use InvalidArgumentException;

class Email implements DataAwareRule, ImplicitRule, Rule, ValidatorAwareRule
{
    use Conditionable, HasEmbeddedRules, HasFieldModifiers, Macroable;

    public bool $validateMxRecord = false;
    public bool $preventSpoofing = false;
    public bool $nativeValidation = false;
    public bool $nativeValidationWithUnicodeAllowed = false;
    public bool $rfcCompliant = false;
    public bool $strictRfcCompliant = false;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * An array of custom rules that will be merged into the validation rules.
     *
     * @var array
     */
    protected $customRules = [];

    /**
     * The field modifiers for the rule.
     */
    protected array $modifiers = [];

    /**
     * The error message after validation, if any.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The callback that will generate the "default" version of the email rule.
     *
     * @var string|array|callable|null
     */
    public static $defaultCallback;

    /**
     * Set the default callback to be used for determining the email default rules.
     *
     * If no arguments are passed, the default email rule configuration will be returned.
     *
     * @param  static|callable|null  $callback
     * @return static|void
     *
     * @phpstan-return ($callback is null ? static : ($callback is callable ? void : ($callback is static ? void : never)))
     *
     * @throws \InvalidArgumentException
     */
    public static function defaults($callback = null)
    {
        if (is_null($callback)) {
            return static::default();
        }

        if (! is_callable($callback) && ! $callback instanceof static) {
            throw new InvalidArgumentException('The given callback should be callable or an instance of '.static::class);
        }

        static::$defaultCallback = $callback;
    }

    /**
     * Get the default configuration of the email rule.
     *
     * @return static
     */
    public static function default()
    {
        $email = is_callable(static::$defaultCallback)
            ? call_user_func(static::$defaultCallback)
            : static::$defaultCallback;

        return $email instanceof static ? $email : new static;
    }

    /**
     * Ensure that the email is an RFC compliant email address.
     *
     * @param  bool  $strict
     * @return $this
     */
    public function rfcCompliant(bool $strict = false)
    {
        if ($strict) {
            $this->strictRfcCompliant = true;
        } else {
            $this->rfcCompliant = true;
        }

        return $this;
    }

    /**
     * Ensure that the email is a strictly enforced RFC compliant email address.
     *
     * @return $this
     */
    public function strict()
    {
        return $this->rfcCompliant(true);
    }

    /**
     * Ensure that the email address has a valid MX record.
     *
     * Requires the PHP intl extension.
     *
     * @return $this
     */
    public function validateMxRecord()
    {
        $this->validateMxRecord = true;

        return $this;
    }

    /**
     * Ensure that the email address is not attempting to spoof another email address using invalid unicode characters.
     *
     * @return $this
     */
    public function preventSpoofing()
    {
        $this->preventSpoofing = true;

        return $this;
    }

    /**
     * Ensure the email address is valid using PHP's native email validation functions.
     *
     * @param  bool  $allowUnicode
     * @return $this
     */
    public function withNativeValidation(bool $allowUnicode = false)
    {
        if ($allowUnicode) {
            $this->nativeValidationWithUnicodeAllowed = true;
        } else {
            $this->nativeValidation = true;
        }

        return $this;
    }

    /**
     * The field under validation must not exceed the given length.
     *
     * This is the only string constraint added directly to Email, because
     * it's the single most common companion rule. All other string
     * constraints should use the escape hatch: ->rule('min:5')
     */
    public function max(int $value): static
    {
        $this->customRules[] = 'max:'.$value;

        return $this;
    }

    /**
     * Specify additional validation rules that should be merged with the default rules during validation.
     *
     * @param  string|array  $rules
     * @return $this
     */
    public function rules($rules)
    {
        $this->customRules = array_merge($this->customRules, Arr::wrap($rules));

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->messages = [];

        $hasPresenceModifier = ! empty(array_intersect($this->modifiers, [
            'required', 'sometimes', 'nullable',
        ])) || ! empty(array_filter($this->modifiers, fn ($m) => is_string($m) && (
            str_starts_with($m, 'required_') || str_starts_with($m, 'exclude')
        )));

        if (! $hasPresenceModifier && ! Arr::has($this->data ?? [], $attribute)) {
            return true;
        }

        $validator = Validator::make(
            $this->data,
            [$attribute => $this->buildValidationRules()],
            $this->validator->customMessages,
            $this->validator->customAttributes
        );

        if ($validator->fails()) {
            $this->messages = array_merge($this->messages, $validator->messages()->all());

            return false;
        }

        return true;
    }

    /**
     * Build the array of underlying validation rules based on the current state.
     *
     * @return array
     */
    protected function buildValidationRules()
    {
        $emailFlags = [];

        if ($this->rfcCompliant) {
            $emailFlags[] = 'rfc';
        }

        if ($this->strictRfcCompliant) {
            $emailFlags[] = 'strict';
        }

        if ($this->validateMxRecord) {
            $emailFlags[] = 'dns';
        }

        if ($this->preventSpoofing) {
            $emailFlags[] = 'spoof';
        }

        if ($this->nativeValidation) {
            $emailFlags[] = 'filter';
        }

        if ($this->nativeValidationWithUnicodeAllowed) {
            $emailFlags[] = 'filter_unicode';
        }

        $rules = [...$this->modifiers];

        if ($emailFlags) {
            $rules[] = 'email:'.implode(',', $emailFlags);
        } else {
            $rules[] = 'email';
        }

        return array_merge(array_filter($rules), $this->customRules);
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the current data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function addRule(array|string|object $rules): static
    {
        if (is_object($rules)) {
            $this->modifiers[] = $rules;
        } else {
            $this->modifiers = array_merge($this->modifiers, Arr::wrap($rules));
        }

        return $this;
    }
}
