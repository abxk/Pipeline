<?php

declare(strict_types=1);

namespace itch;

use Closure;
use Psr\Container\ContainerInterface;

/**
 * This file mostly code come from illuminate/pipe and hyperf/utils,
 * thanks provide such a useful class.
 */
class Pipeline
{
    /**
     * 容器实现。
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 通过管道传递的对象。
     *
     * @var mixed
     */
    protected $passable;

    /**
     * 类管道的数组。
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * 每个管道上要调用的方法。
     *
     * @var string
     */
    protected $method = 'handle';

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 设置通过管道发送的对象或数据。
     *
     * @param mixed $passable
     * @return Pipeline
     */
    public function send($passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * 调用栈。
     *
     * @param array|mixed $pipes
     * @return Pipeline
     */
    public function through($pipes): self
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * 设置调用管道的方法。
     * @param string $method
     * @return Pipeline
     */
    public function via(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * 执行。
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination));

        return $pipeline($this->passable);
    }

    /**
     * 目的地
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return static function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    /**
     * 获取一个表示应用程序的一个部分的闭包。
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    // 如果管道是一个Closure的实例，我们将直接调用它
                    // 否则，我们将从容器中解析管道并调用它适当的方法和参数，并返回结果。
                    return $pipe($passable, $stack);
                }
                if (!is_object($pipe)) {
                    [$name, $parameters] = $this->parsePipeString($pipe);

                    // 如果管道是一个字符串，我们将解析字符串并解析出类
                    // 依赖注入容器。然后，我们可以构建一个可调用的和
                    // 执行管道函数给出的参数是必需的。
                    $pipe = $this->container->get($name);

                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    // 如果管道已经是一个对象，我们只需要创建一个可调用对象并将其传递给
                    // 管道原样。不需要做任何额外的解析和格式化
                    // 因为我们给定的对象已经是一个完全实例化的对象
                    $parameters = [$passable, $stack];
                }

                $carry = method_exists($pipe, $this->method) ? $pipe->{$this->method}(...$parameters) : $pipe(...$parameters);

                return $this->handleCarry($carry);
            };
        };
    }

    /**
     * 解析完整的管道字符串以获得名称和参数。
     * @param string $pipe
     * @return array
     */
    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * 在将每个管道返回的值传递给下一个管道之前，先处理它。
     *
     * @param mixed $carry
     *
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }
}
