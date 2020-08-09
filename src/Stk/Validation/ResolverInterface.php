<?php

namespace Stk\Validation;

use Respect\Validation\Validator;

interface ResolverInterface
{
    public function resolve(RuleInterface $rule): ?Validator;
}