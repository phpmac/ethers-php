<?php

declare(strict_types=1);

namespace Ethers\Contract;

use RuntimeException;

/**
 * ContractFunction
 * 合约函数包装类, 提供 ethers.js v6 风格的函数调用方式
 *
 * 参考 ethers.js v6:
 * - contract.transfer(to, amount) - 直接调用
 * - contract.transfer.staticCall(to, amount) - 模拟调用
 * - contract.transfer.send(to, amount) - 发送交易
 * - contract.transfer.estimateGas(to, amount) - 估算 gas
 * - contract.transfer.populateTransaction(to, amount) - 构建交易
 */
class ContractFunction
{
    private BaseContract $contract;

    private array $fragment;

    public function __construct(BaseContract $contract, array $fragment)
    {
        $this->contract = $contract;
        $this->fragment = $fragment;
    }

    /**
     * 获取函数名
     */
    public function getName(): string
    {
        return $this->fragment['name'];
    }

    /**
     * 获取函数签名
     */
    public function getSignature(): string
    {
        return $this->fragment['signature'];
    }

    /**
     * 获取函数选择器
     */
    public function getSelector(): string
    {
        return $this->fragment['selector'];
    }

    /**
     * 获取 stateMutability
     */
    public function getStateMutability(): string
    {
        return $this->fragment['stateMutability'];
    }

    /**
     * 获取输入参数定义
     */
    public function getInputs(): array
    {
        return $this->fragment['inputs'];
    }

    /**
     * 获取输出参数定义
     */
    public function getOutputs(): array
    {
        return $this->fragment['outputs'];
    }

    /**
     * 获取函数片段
     */
    public function getFragment(): array
    {
        return $this->fragment;
    }

    /**
     * 模拟调用 (staticCall) - 不改变链上状态
     *
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数
     * @return mixed 返回值
     */
    public function staticCall(array $args = [], array $overrides = []): mixed
    {
        $provider = $this->contract->getProvider();
        if ($provider === null) {
            throw new RuntimeException('需要 Provider 才能调用合约方法');
        }

        $data = $this->contract->getInterface()->encodeFunctionData($this->getName(), $args);

        $tx = array_merge([
            'to' => $this->contract->target,
            'data' => $data,
        ], $overrides);

        // 如果有 signer, 添加 from
        $signer = $this->contract->getSigner();
        if ($signer !== null && ! isset($tx['from'])) {
            $tx['from'] = $signer->getAddress();
        }

        $result = $provider->call($tx);
        $decoded = $this->contract->getInterface()->decodeFunctionResult($this->getName(), $result);

        // 如果只有一个返回值, 直接返回值 (符合 ethers.js 行为)
        $outputs = $this->fragment['outputs'] ?? [];
        if (count($outputs) === 1) {
            return $decoded[0];
        }

        return $decoded;
    }

    /**
     * 发送交易调用方法
     *
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数
     * @return array{hash: string, wait: callable}
     */
    public function send(array $args = [], array $overrides = []): array
    {
        $signer = $this->contract->getSigner();
        if ($signer === null) {
            throw new RuntimeException('需要 Signer 才能发送交易');
        }

        $data = $this->contract->getInterface()->encodeFunctionData($this->getName(), $args);

        $tx = array_merge([
            'to' => $this->contract->target,
            'data' => $data,
        ], $overrides);

        return $signer->sendTransaction($tx);
    }

    /**
     * 估算 gas
     *
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数
     * @return string gas 数量 (BigInt 字符串)
     */
    public function estimateGas(array $args = [], array $overrides = []): string
    {
        $provider = $this->contract->getProvider();
        if ($provider === null) {
            throw new RuntimeException('需要 Provider 才能估算 gas');
        }

        $data = $this->contract->getInterface()->encodeFunctionData($this->getName(), $args);

        $tx = array_merge([
            'to' => $this->contract->target,
            'data' => $data,
        ], $overrides);

        // 如果有 signer, 添加 from
        $signer = $this->contract->getSigner();
        if ($signer !== null && ! isset($tx['from'])) {
            $tx['from'] = $signer->getAddress();
        }

        return $provider->estimateGas($tx);
    }

    /**
     * 构建交易对象 (不发送)
     *
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数
     * @return array 交易对象
     */
    public function populateTransaction(array $args = [], array $overrides = []): array
    {
        $data = $this->contract->getInterface()->encodeFunctionData($this->getName(), $args);

        $tx = array_merge([
            'to' => $this->contract->target,
            'data' => $data,
        ], $overrides);

        // 如果有 signer, 添加 from
        $signer = $this->contract->getSigner();
        if ($signer !== null && ! isset($tx['from'])) {
            $tx['from'] = $signer->getAddress();
        }

        return $tx;
    }

    /**
     * 直接调用 (根据 stateMutability 决定调用方式)
     *
     * @param  mixed  ...$args  参数
     */
    public function __invoke(mixed ...$args): mixed
    {
        // 最后一个参数可能是 overrides
        $overrides = [];
        if (! empty($args) && is_array(end($args))) {
            $lastArg = end($args);
            if (isset($lastArg['value']) || isset($lastArg['gas']) || isset($lastArg['gasLimit']) || isset($lastArg['from'])) {
                $overrides = array_pop($args);
            }
        }

        // 根据 stateMutability 决定调用方式
        if (in_array($this->getStateMutability(), ['view', 'pure'])) {
            return $this->staticCall($args, $overrides);
        }

        return $this->send($args, $overrides);
    }
}
