<?php

namespace Stk\Validation;

use Stk\Immutable\MapInterface;
use Stk\Service\Injectable;
use Stk\Validation\Resolver\Respect;

class MapValidator implements Injectable
{
    public function validate(MapInterface $data, array $schema): array
    {
        $errors = [];
        foreach ($schema as $def) {

            $rule = Rule::fromArray($def);

            if ($this->resolveWildcard($data, $rule, $errors)) {
                continue;
            }

            if (isset($errors[$rule->getKey()])) {
                continue;
            }

            $res = $this->resolveValidate($rule, $data->getIn($rule->getField()));
            if (!$res) {
                $errors[$rule->getKey()] = $rule->getMessage();
            }
        }

        return $errors;
    }

    /**
     * @param RuleInterface $rule
     * @param mixed $value
     * @return bool
     */
    protected function resolveValidate(RuleInterface $rule, $value): bool
    {
        $resolver  = new Respect();
        $validator = $resolver->resolve($rule);
        if ($validator === null) {
            return false;
        }

        return $validator->validate($value);
    }

    protected function resolveWildcard(MapInterface $data, RuleInterface $rule, array &$errors): bool
    {
        if (array_search('*', $rule->getField(), true) === false) {
            return false;
        }

        $data->walk(function ($path, $value) use ($rule, &$errors) {
            $collected = [];
            $key       = implode('.', $path);

            if (isset($errors[$key])) {
                return;
            }

            foreach ($rule->getField() as $idx => $f) {
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
                if (!$this->resolveValidate($rule, $value)) {
                    $errors[$key] = $rule->getMessage();
                }

            }
        });

        return true;
    }
}
