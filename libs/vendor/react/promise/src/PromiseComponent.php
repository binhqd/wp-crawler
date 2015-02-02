<?php

namespace React\Promise;

class PromiseComponent
{
	public function resolve($promiseOrValue = null)
	{
	    if ($promiseOrValue instanceof PromiseInterface) {
	        return $promiseOrValue;
	    }

	    return new FulfilledPromise($promiseOrValue);
	}

	public function reject($promiseOrValue = null)
	{
	    if ($promiseOrValue instanceof PromiseInterface) {
	        return $promiseOrValue->then(function ($value) {
	            return new RejectedPromise($value);
	        });
	    }

	    return new RejectedPromise($promiseOrValue);
	}

	public function all($promisesOrValues)
	{
	    return $this->map($promisesOrValues, function ($val) {
	        return $val;
	    });
	}

	public function race($promisesOrValues)
	{
	    return $this->resolve($promisesOrValues)
	        ->then(function ($array) {
	            if (!is_array($array) || !$array) {
	                return $this->resolve();
	            }

	            return new Promise(function ($resolve, $reject, $progress) use ($array) {
	                foreach ($array as $promiseOrValue) {
	                    $this->resolve($promiseOrValue)
	                        ->then($resolve, $reject, $progress);
	                }
	            });
	        });
	}

	public function any($promisesOrValues)
	{
	    return $this->some($promisesOrValues, 1)
	        ->then(function ($val) {
	            return array_shift($val);
	        });
	}

	public function some($promisesOrValues, $howMany)
	{
	    return $this->resolve($promisesOrValues)
	        ->then(function ($array) use ($howMany) {
	            if (!is_array($array) || !$array || $howMany < 1) {
	                return $this->resolve([]);
	            }

	            return new Promise(function ($resolve, $reject, $progress) use ($array, $howMany) {
	                $len       = count($array);
	                $toResolve = min($howMany, $len);
	                $toReject  = ($len - $toResolve) + 1;
	                $values    = [];
	                $reasons   = [];

	                foreach ($array as $i => $promiseOrValue) {
	                    $fulfiller = function ($val) use ($i, &$values, &$toResolve, $toReject, $resolve) {
	                        if ($toResolve < 1 || $toReject < 1) {
	                            return;
	                        }

	                        $values[$i] = $val;

	                        if (0 === --$toResolve) {
	                            $resolve($values);
	                        }
	                    };

	                    $rejecter = function ($reason) use ($i, &$reasons, &$toReject, $toResolve, $reject) {
	                        if ($toResolve < 1 || $toReject < 1) {
	                            return;
	                        }

	                        $reasons[$i] = $reason;

	                        if (0 === --$toReject) {
	                            $reject($reasons);
	                        }
	                    };

	                    $this->resolve($promiseOrValue)
	                        ->then($fulfiller, $rejecter, $progress);
	                }
	            });
	        });
	}

	public function map($promisesOrValues, callable $mapFunc)
	{
	    return $this->resolve($promisesOrValues)
	        ->then(function ($array) use ($mapFunc) {
	            if (!is_array($array) || !$array) {
	                return resolve([]);
	            }

	            return new Promise(function ($resolve, $reject, $progress) use ($array, $mapFunc) {
	                $toResolve = count($array);
	                $values    = [];

	                foreach ($array as $i => $promiseOrValue) {
	                    $this->resolve($promiseOrValue)
	                        ->then($mapFunc)
	                        ->then(
	                            function ($mapped) use ($i, &$values, &$toResolve, $resolve) {
	                                $values[$i] = $mapped;

	                                if (0 === --$toResolve) {
	                                    $resolve($values);
	                                }
	                            },
	                            $reject,
	                            $progress
	                        );
	                }
	            });
	        });
	}

	public function reduce($promisesOrValues, callable $reduceFunc , $initialValue = null)
	{
	    return $this->resolve($promisesOrValues)
	        ->then(function ($array) use ($reduceFunc, $initialValue) {
	            if (!is_array($array)) {
	                $array = [];
	            }

	            $total = count($array);
	            $i = 0;

	            // Wrap the supplied $reduceFunc with one that handles promises and then
	            // delegates to the supplied.
	            $wrappedReduceFunc = function ($current, $val) use ($reduceFunc, $total, &$i) {
	                return $this->resolve($current)
	                    ->then(function ($c) use ($reduceFunc, $total, &$i, $val) {
	                        return $this->resolve($val)
	                            ->then(function ($value) use ($reduceFunc, $total, &$i, $c) {
	                                return $reduceFunc($c, $value, $i++, $total);
	                            });
	                    });
	            };

	            return array_reduce($array, $wrappedReduceFunc, $initialValue);
	        });
	}

}