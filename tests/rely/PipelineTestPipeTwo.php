<?php


namespace itch\tests\rely;


/**
 * 当尝试以调用函数的方式调用一个对象时，__invoke() 方法会被自动调用。
 * @package itch\tests
 */
class PipelineTestPipeTwo
{
    public function __invoke($piped, $next)
    {
        $_SERVER['__test.pipe.one'] = $piped;

        return $next($piped);
    }
}