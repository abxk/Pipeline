<?php


namespace abxk\tests\rely;


class PipelineTestPipeOne
{
    public function handle($piped, $next)
    {
        $_SERVER['__test.pipe.one'] = $piped;

        return $next($piped);
    }

    public function differentMethod($piped, $next)
    {
        return $next($piped);
    }

    public function incr($piped, $next)
    {
        dump("7: -" . $piped);
        dump("8: -" . $next($piped));
        return $next($piped);

    }
}