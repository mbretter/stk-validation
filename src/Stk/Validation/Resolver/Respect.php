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

            $v = call_user_func_array([Validator::class, $ruleName], $args);
        } else {
            $v = call_user_func_array([Validator::class, $ruleName], $rule);
        }

        return $v;
    }

}
