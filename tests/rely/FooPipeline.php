<?php


namespace itch\tests\rely;


use itch\Pipeline;

class FooPipeline extends Pipeline
{
    protected function handleCarry($carry)
    {
        dump( "1: -" .$carry);
        $carry = parent::handleCarry($carry);
        if (is_int($carry)) {
            $carry += 2;
        }
        dump( "2: -" .$carry);
        return $carry;
    }
}