<?php

declare(strict_types=1);

namespace abxk\test;

use abxk\Pipeline;
use abxk\test\rely\FooPipeline;
use abxk\test\rely\PipelineTestParameterPipe;
use abxk\test\rely\PipelineTestPipeOne;
use abxk\test\rely\PipelineTestPipeTwo;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 * @coversNothing
 */
class PipelineTest extends TestCase
{
    /**
     * 实例化数据接口
     *
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|ContainerInterface
     *
     */
    protected function getContainer()
    {
        $container = Mockery::mock(ContainerInterface::class);
        $container->shouldReceive('get')->with(PipelineTestPipeOne::class)->andReturn(new PipelineTestPipeOne());
        $container->shouldReceive('get')->with(PipelineTestPipeTwo::class)->andReturn(new PipelineTestPipeTwo());
        $container->shouldReceive('get')->with(PipelineTestParameterPipe::class)->andReturn(new PipelineTestParameterPipe());

        return $container;
        
    }

    /**
     * 用于关闭和验证全局容器中的所有模拟
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * 测试管道使用对象
     */
    public function testPipelineUsageWithObjects()
    {
        $result = (new Pipeline($this->getContainer()))
            ->send('foo')
            ->through([new PipelineTestPipeOne()])
            ->then(function ($piped) {
                return $piped;
            });

        static::assertSame('foo', $result);
        static::assertSame('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    /**
     * 测试可调用对象的管道使用情况
     */
    public function testPipelineUsageWithInvokableObjects()
    {

        $result = (new Pipeline($this->getContainer()))
            ->send('foo')
            ->through([new PipelineTestPipeTwo()])
            ->then(
                function ($piped) {
                    return $piped . "123";
                }
            );

        static::assertSame('foo123', $result);
        static::assertSame('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }


    /**
     * 测试使用可调用类的管道
     */
    public function testPipelineUsageWithInvokableClass()
    {
        $result = (new Pipeline($this->getContainer()))
            ->send('foo')
            ->through([PipelineTestPipeTwo::class])
            ->then(
                function ($piped) {
                    return $piped;
                }
            );

        static::assertSame('foo', $result);
        static::assertSame('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    public function testPipelineUsageWithCallable()
    {
        $function = function ($piped, $next) {
            $_SERVER['__test.pipe.one'] = 'foo';

            return $next($piped);
        };

        $result = (new Pipeline($this->getContainer()))
            ->send('foo')
            ->through([$function])
            ->then(
                function ($piped) {
                    return $piped;
                }
            );

        static::assertSame('foo', $result);
        static::assertSame('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);

        $result = (new Pipeline($this->getContainer()))
            ->send('bar')
            ->through($function)
            ->then(static function ($passable) {
                return $passable;
            });

        static::assertSame('bar', $result);
        static::assertSame('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }


    /**
     *
     */
    public function testPipelineBasicUsage()
    {
        $pipeTwo = function ($piped, $next) {
            $_SERVER['__test.pipe.two'] = $piped;

            return $next($piped);
        };

        $result = (new Pipeline($this->getContainer()))
            ->send('foo')
            ->through(PipelineTestPipeOne::class, $pipeTwo)
//            ->via('handle')
            ->then(function ($piped) {
                return $piped;
            });
//        dump($_SERVER);
//        dump($result);
        static::assertSame('foo', $result);
        static::assertSame('foo', $_SERVER['__test.pipe.one']);
        static::assertSame('foo', $_SERVER['__test.pipe.two']);

        unset($_SERVER['__test.pipe.one'], $_SERVER['__test.pipe.two']);
    }

    /**
     * 测试管道使用参数
     */
    public function testPipelineUsageWithParameters()
    {
        $parameters = ['one', 'two'];

        $result = (new Pipeline($this->getContainer()))
            ->send('foo')
            ->through(PipelineTestParameterPipe::class . ':' . implode(',', $parameters))
            ->then(function ($piped) {
                return $piped;
            });

        static::assertSame('foo', $result);
        static::assertEquals($parameters, $_SERVER['__test.pipe.parameters']);

        unset($_SERVER['__test.pipe.parameters']);
    }

    public function testPipelineViaChangesTheMethodBeingCalledOnThePipes()
    {
        $pipelineInstance = new Pipeline($this->getContainer());
        $result = $pipelineInstance->send('data')
            ->through(PipelineTestPipeOne::class)
            ->via('differentMethod')
            ->then(function ($piped) {
                return $piped;
            });
        static::assertSame('data', $result);
    }

    public function testPipelineThenReturnMethodRunsPipelineThenReturnsPassable()
    {
        $result = (new Pipeline($this->getContainer()))
            ->send('foo')
            ->through([PipelineTestPipeOne::class])
            ->then(static function ($passable) {
                return $passable;
            });

        static::assertSame('foo', $result);
        static::assertSame('foo', $_SERVER['__test.pipe.one']);

        unset($_SERVER['__test.pipe.one']);
    }

    public function testHandleCarry()
    {
        $result = (new FooPipeline($this->getContainer()))
            ->send($id = rand(0, 99))
            ->through([PipelineTestPipeOne::class])
            ->via('incr')
            ->then(static function ($passable) {
                dump("3: ---" . $passable);
                if (is_int($passable)) {
                    $passable += 3;
                }
                dump("4: ---" . $passable);
                return $passable;
            });
        dump( "5: ---" . $id);
        dump( "6: ---" . $result);
        static::assertSame($id + 5, $result);
    }
}