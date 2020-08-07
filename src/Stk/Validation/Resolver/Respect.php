<?php

namespace Stk\Validation\Resolver;

use InvalidArgumentException;
use Respect\Validation\Validator;
use Respect\Validation\Validator as v;
use Stk\Immutable\MapInterface;
use Stk\Service\Injectable;
use Stk\Validation\ResolverInterface;

class Respect implements Injectable, ResolverInterface
{
    /**
     * @param MapInterface $data
     * @param array $schema
     *
     * @return array
     */
    public function resolve(MapInterface $data, array $schema): array
    {
        $errors = [];
        foreach ($schema as $def) {
            if (!isset($def['field']) || !isset($def['rule']) || !isset($def['message'])) {
                throw new InvalidArgumentException('invalid rule definition.');
            }

            $def = $this->normalize($def);

            $key = implode('.', $def['field']);

            if ($this->resolveWildcard($data, $def, $errors)) {
                continue;
            }

            if (isset($errors[$key])) {
                continue;
            }

            $res = $this->validate($def['rule'], $data->getIn($def['field']));
            if (!$res) {
                $errors[$key] = $def['message'];
            }
        }

        return $errors;
    }

    protected function validate(array $rule, $value): bool
    {
        $v = $this->buildChain($rule);
        if ($v === null) {
            return false;
        }

        return $v->validate($value);
    }

    protected function resolveWildcard(MapInterface $data, array $def, array &$errors): bool
    {
        $found     = false;
        $fieldPath = $def['field'];
        foreach ($fieldPath as $idx => $f) {
            if ($f === '*') {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }

        $data->walk(function ($path, $value) use ($fieldPath, $def, &$errors) {
            $collected = [];
            $key       = implode('.', $path);

            if (isset($errors[$key])) {
                return;
            }

            foreach ($fieldPath as $idx => $f) {
                if (!isset($path[$idx])) {
                    break;
                }

                if ($f === '*') {
                    $collected[] = $path[$idx];
                } elseif ($f === $path[$idx]) {
                    $collected[] = $f;
                }
            }

            if ($collected == $path) {
                if (!$this->validate($def['rule'], $value)) {
                    $errors[$key] = $def['message'];
                }

            }

        });

        return true;
    }


    /**
     * 'rule'  => ['allOf', [
     * ['regex', '/^[\w_.-]+$/i'],
     * ['length', 2, 40]
     * ]],
     *
     * @param array $rule
     *
     * @return mixed
     */
    protected function buildChain(array $rule): ?Validator
    {
        if (count($rule) === 0) {
            return null;
        }

        $ruleName = array_shift($rule);
        if (in_array($ruleName, ['allOf', 'anyOf', 'noneOf', 'oneOf', 'not'])) {
            $args = [];
            foreach ($rule as $r) {
                $args[] = $this->buildChain(is_array($r) ? $r : [$r]);
            }

            $v = call_user_func_array([v::class, $ruleName], $args);
        } else {
            $v = call_user_func_array([v::class, $ruleName], $rule);
        }

        return $v;
    }

    protected function normalize(array $def): array
    {

        $def['field'] = is_array($def['field']) ? $def['field'] : [$def['field']];
        $def['rule']  = is_array($def['rule']) ? $def['rule'] : [$def['rule']];

        return $def;
    }
}
