<?php

namespace Illuminate\Validation\Rules\Concerns;

use Closure;
use Illuminate\Validation\Rule;

trait HasEmbeddedRules
{
    /**
     * The field under validation must be unique in the given database table.
     *
     * For complex cases (->ignore(), ->where(), etc.), use the rule() escape hatch:
     * ->rule(Rule::unique('users', 'email')->ignore($user->id))
     */
    public function unique(string $table, ?string $column = null): static
    {
        return $this->addRule(Rule::unique($table, $column ?? 'NULL'));
    }

    /**
     * The field under validation must exist in the given database table.
     *
     * For complex cases (->where(), etc.), use the rule() escape hatch:
     * ->rule(Rule::exists('countries', 'code')->where('active', true))
     */
    public function exists(string $table, ?string $column = null): static
    {
        return $this->addRule(Rule::exists($table, $column ?? 'NULL'));
    }

    /**
     * The field under validation must be a valid enum value.
     *
     * @param  class-string  $type
     */
    public function enum(string $type, ?Closure $callback = null): static
    {
        $rule = Rule::enum($type);

        if ($callback) {
            $callback($rule);
        }

        return $this->addRule($rule);
    }

    /**
     * The field under validation must be included in the given list of values.
     */
    public function in(array $values): static
    {
        return $this->addRule(Rule::in($values));
    }

    /**
     * The field under validation must not be included in the given list of values.
     */
    public function notIn(array $values): static
    {
        return $this->addRule(Rule::notIn($values));
    }
}
