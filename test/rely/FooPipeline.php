<?php


namespace abxk\test\rely;


use abxk\Pipeline;

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