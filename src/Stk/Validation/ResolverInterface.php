<?php

namespace Stk\Validation;

use Stk\Immutable\MapInterface;

interface ResolverInterface
{
    public function resolve(MapInterface $data, array $schema): array;

}