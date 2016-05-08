<?php

namespace AceOugi;

class Runner
{
    /** @var callable */
    protected $resolver;

    /** @var \SplQueue */
    protected $queue;

    /**
     * Runner constructor.
     * @param callable|null $resolver
     */
    public function __construct(callable $resolver = null)
    {
        $this->resolver = $resolver;
        $this->queue = new \SplQueue();
    }

    /**
     * @param callable $callable
     * @return callable
     */
    protected function resolve($callable) : callable
    {
        return ($resolver = $this->resolver) ? $resolver($callable) : $callable;
    }

    /**
     * @param $callable
     * @return self
     */
    public function add(...$callables)
    {
        foreach ($callables as $callable)
            $this->queue->push($callable);

        return $this;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return mixed
     */
    public function call(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
    {
        if ($this->queue->isEmpty())
            return $response;

        return call_user_func($this->resolve($this->queue->dequeue()), $request, $response, $this);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable[] ...$callables
     * @return mixed
     */
    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, ...$callables)
    {
        while ($callable = array_pop($callables))
            $this->queue->unshift($callable);

        return $this->dispatch($request, $response);
    }
}
