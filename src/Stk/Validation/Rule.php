<?php

namespace Stk\Validation;

use InvalidArgumentException;

use Stk\Service\Injectable;

class Rule implements Injectable, RuleInterface
{
    protected array $field;

    protected array $rule;

    protected string $message;

    protected array $key;

    /**
     * Rule constructor.
     *
     * @param array $field
     * @param array $rule
     * @param string $message
     * @param array $key
     */
    public function __construct(array $field, array $rule, string $message, array $key)
    {
        $this->field   = $field;
        $this->rule    = $rule;
        $this->message = $message;
        $this->key     = $key;
    }

    public static function fromArray(array $def): RuleInterface
    {
        if (!isset($def['field']) || !isset($def['rule']) || !isset($def['message'])) {
            throw new InvalidArgumentException('invalid rule definition.');
        }

        $def = self::normalize($def);

        return new self($def['field'], $def['rule'], $def['message'], $def['key']);
    }

    protected static function normalize(array $def): array
    {
        $def['field'] = is_array($def['field']) ? $def['field'] : [$def['field']];
        $def['rule']  = is_array($def['rule']) ? $def['rule'] : [$def['rule']];

        if (isset($def['key'])) {
            $def['key'] = is_array($def['key']) ? $def['key'] : [$def['key']];
        } else {
            $def['key'] = $def['field'];
        }

        return $def;
    }

    /**
     * @return array
     */
    public function getField(): array
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getRule(): array
    {
        return $this->rule;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    public function getKey(): string
    {
        return implode('.', $this->key);
    }
}
