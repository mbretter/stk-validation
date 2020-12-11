<?php

namespace Stk\Validation\Resolver;

use Respect\Validation\Validator;
use Stk\Service\Injectable;
use Stk\Validation\ResolverInterface;
use Stk\Validation\RuleInterface;

class Respect implements Injectable, ResolverInterface
{
    public function resolve(RuleInterface $rule): ?Validator
    {
        $v = $this->buildChain($rule->getRule());
        if ($v === null) {
            return null;
        }

        return $v;
    }

    /**
     *
     * @param array $rule
     * @param Validator|null $v
     *
     * @return mixed
     */
    protected function buildChain(array $rule, Validator $v = null): ?Validator
    {
        if (count($rule) === 0) {
            return null;
        }

        if ($v === null) {
            $v = Validator::create();
        }

        $hasChain = is_array($rule[0]);

        if ($hasChain) {
            foreach ($rule as $r) {
                $v = $this->buildChain($r, $v);
            }
        } else {
            $ruleName = array_shift($rule);

            if (in_array($ruleName, ['allOf', 'anyOf', 'noneOf', 'oneOf', 'not', 'nullable', 'optional'])) {
                $args = [];
                foreach ($rule as $r) {
                    $args[] = $this->buildChain(is_array($r) ? $r : [$r]);
                }

                $v = call_user_func_array([$v, $ruleName], $args);
            } else {
                $v = call_user_func_array([$v, $ruleName], $rule);
            }
        }

        return $v;
    }

}
