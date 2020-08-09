<?php

namespace Stk\Validation;


interface RuleInterface
{

    /**
     * @return array
     */
    public function getField(): array;

    /**
     * @return array
     */
    public function getRule(): array;

    /**
     * @return string
     */
    public function getMessage(): string;

    /**
     * @return string
     */
    public function getKey(): string;
}