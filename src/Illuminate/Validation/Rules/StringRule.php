<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\FluentRule;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\Rules\Concerns\HasEmbeddedRules;
use Illuminate\Validation\Rules\Concerns\HasFieldModifiers;
use IteratorAggregate;
use Stringable;
use Traversable;

class StringRule implements FluentRule, IteratorAggregate, Stringable
{
    use Conditionable, HasEmbeddedRules, HasFieldModifiers, Macroable;

    /**
     * The string constraints for the rule.
     */
    protected array $constraints = ['string'];

    /**
     * The field under validation must be entirely alphabetic characters.
     *
     * @param  bool  $ascii
     * @return $this
     */
    public function alpha(bool $ascii = false): static
    {
        return $this->addRule($ascii ? 'alpha:ascii' : 'alpha');
    }

    /**
     * The field under validation must be entirely alpha-numeric characters, dashes, and underscores.
     *
     * @param  bool  $ascii
     * @return $this
     */
    public function alphaDash(bool $ascii = false): static
    {
        return $this->addRule($ascii ? 'alpha_dash:ascii' : 'alpha_dash');
    }

    /**
     * The field under validation must be entirely alpha-numeric characters.
     *
     * @param  bool  $ascii
     * @return $this
     */
    public function alphaNumeric(bool $ascii = false): static
    {
        return $this->addRule($ascii ? 'alpha_num:ascii' : 'alpha_num');
    }

    /**
     * The field under validation must be entirely ASCII characters.
     *
     * @return $this
     */
    public function ascii(): static
    {
        return $this->addRule('ascii');
    }

    /**
     * The field under validation must have a length between the given min and max (inclusive).
     *
     * @param  int  $min
     * @param  int  $max
     * @return $this
     */
    public function between(int $min, int $max): static
    {
        return $this->addRule('between:'.$min.','.$max);
    }

    /**
     * The field under validation must not end with any of the given values.
     *
     * @param  string  ...$values
     * @return $this
     */
    public function doesntEndWith(string ...$values): static
    {
        return $this->addRule('doesnt_end_with:'.implode(',', $values));
    }

    /**
     * The field under validation must not start with any of the given values.
     *
     * @param  string  ...$values
     * @return $this
     */
    public function doesntStartWith(string ...$values): static
    {
        return $this->addRule('doesnt_start_with:'.implode(',', $values));
    }

    /**
     * The field under validation must end with one of the given values.
     *
     * @param  string  ...$values
     * @return $this
     */
    public function endsWith(string ...$values): static
    {
        return $this->addRule('ends_with:'.implode(',', $values));
    }

    /**
     * The field under validation must have an exact length.
     *
     * @param  int  $value
     * @return $this
     */
    public function exactly(int $value): static
    {
        return $this->addRule('size:'.$value);
    }

    /**
     * The field under validation must be entirely lowercase.
     *
     * @return $this
     */
    public function lowercase(): static
    {
        return $this->addRule('lowercase');
    }

    /**
     * The field under validation must not exceed the given length.
     *
     * @param  int  $value
     * @return $this
     */
    public function max(int $value): static
    {
        return $this->addRule('max:'.$value);
    }

    /**
     * The field under validation must have a minimum length.
     *
     * @param  int  $value
     * @return $this
     */
    public function min(int $value): static
    {
        return $this->addRule('min:'.$value);
    }

    /**
     * The field under validation must start with one of the given values.
     *
     * @param  string  ...$values
     * @return $this
     */
    public function startsWith(string ...$values): static
    {
        return $this->addRule('starts_with:'.implode(',', $values));
    }

    /**
     * The field under validation must be entirely uppercase.
     *
     * @return $this
     */
    public function uppercase(): static
    {
        return $this->addRule('uppercase');
    }

    /**
     * The field under validation must be a valid URL.
     */
    public function url(): static
    {
        return $this->addRule('url');
    }

    /**
     * The field under validation must be a valid URL with an active DNS record.
     */
    public function activeUrl(): static
    {
        return $this->addRule('active_url');
    }

    /**
     * The field under validation must be a valid UUID.
     */
    public function uuid(): static
    {
        return $this->addRule('uuid');
    }

    /**
     * The field under validation must be a valid ULID.
     */
    public function ulid(): static
    {
        return $this->addRule('ulid');
    }

    /**
     * The field under validation must be a valid JSON string.
     */
    public function json(): static
    {
        return $this->addRule('json');
    }

    /**
     * The field under validation must be an IP address.
     */
    public function ip(): static
    {
        return $this->addRule('ip');
    }

    /**
     * The field under validation must be an IPv4 address.
     */
    public function ipv4(): static
    {
        return $this->addRule('ipv4');
    }

    /**
     * The field under validation must be an IPv6 address.
     */
    public function ipv6(): static
    {
        return $this->addRule('ipv6');
    }

    /**
     * The field under validation must be a MAC address.
     */
    public function macAddress(): static
    {
        return $this->addRule('mac_address');
    }

    /**
     * The field under validation must match the given regular expression.
     */
    public function regex(string $pattern): static
    {
        return $this->addRule('regex:'.$pattern);
    }

    /**
     * The field under validation must not match the given regular expression.
     */
    public function notRegex(string $pattern): static
    {
        return $this->addRule('not_regex:'.$pattern);
    }

    /**
     * The field under validation must be a valid timezone identifier.
     */
    public function timezone(): static
    {
        return $this->addRule('timezone');
    }

    /**
     * The field under validation must be a valid hex color.
     */
    public function hexColor(): static
    {
        return $this->addRule('hex_color');
    }

    /**
     * The field under validation must be a valid date.
     */
    public function date(): static
    {
        return $this->addRule('date');
    }

    /**
     * The field under validation must match the given date format.
     */
    public function dateFormat(string $format): static
    {
        return $this->addRule('date_format:'.$format);
    }

    /**
     * The field under validation must match the value of the confirmation field.
     */
    public function confirmed(): static
    {
        return $this->addRule('confirmed');
    }

    /**
     * The field under validation must match the authenticated user's password.
     *
     * @param  string|null  $guard
     */
    public function currentPassword(?string $guard = null): static
    {
        return $this->addRule($guard ? 'current_password:'.$guard : 'current_password');
    }

    /**
     * The field under validation must have the same value as the given field.
     */
    public function same(string $field): static
    {
        return $this->addRule('same:'.$field);
    }

    /**
     * The field under validation must have a different value than the given field.
     */
    public function different(string $field): static
    {
        return $this->addRule('different:'.$field);
    }

    /**
     * The field under validation must exist in another field's array.
     */
    public function inArray(string $field): static
    {
        return $this->addRule('in_array:'.$field);
    }

    /**
     * The field under validation must exist as a key in another field's array.
     */
    public function inArrayKeys(string $field): static
    {
        return $this->addRule('in_array_keys:'.$field);
    }

    /**
     * Each value in the field under validation must be unique.
     */
    public function distinct(?string $mode = null): static
    {
        return $this->addRule($mode ? 'distinct:'.$mode : 'distinct');
    }

    /**
     * Get an iterator for the validation rules.
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator([
            ...array_unique($this->constraints),
            ...$this->rules,
        ]);
    }

    /**
     * Convert the rule to a validation string.
     *
     * Lossy — only returns string constraints. Embedded rule objects
     * are NOT included. The parser uses getIterator() which returns
     * everything.
     */
    public function __toString(): string
    {
        if (! empty($this->rules)) {
            trigger_error(
                'Casting '.static::class.' to string discards embedded rule objects. Use the rule object directly instead of casting to string.',
                E_USER_DEPRECATED,
            );
        }

        return implode('|', array_unique($this->constraints));
    }
}
