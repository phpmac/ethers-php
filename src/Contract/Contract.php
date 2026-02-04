<?php

declare(strict_types=1);

namespace Ethers\Contract;

use Ethers\Provider\JsonRpcProvider;
use Ethers\Signer\Wallet;

/**
 * Contract
 * 合约类, 用于与智能合约交互
 *
 * 参考 ethers.js v6 的 Contract
 *
 * 支持两种创建方式:
 * 1. JSON ABI:
 *    $contract = new Contract($address, $jsonAbi, $signer);
 *
 * 2. 人类可读 ABI:
 *    $contract = new Contract($address, [
 *        "function name() view returns (string)",
 *        "function transfer(address to, uint256 amount) returns (bool)",
 *        "event Transfer(address indexed from, address indexed to, uint256 value)",
 *    ], $signer);
 *
 * 调用方式:
 * - $contract->name() - 调用只读方法
 * - $contract->transfer($to, $amount) - 发送交易
 * - $contract->transfer->staticCall([$to, $amount]) - 模拟调用
 * - $contract->transfer->estimateGas([$to, $amount]) - 估算 gas
 */
class Contract extends BaseContract
{
    /**
     * 构造函数
     *
     * @param  string  $address  合约地址
     * @param  array|string  $abi  ABI 定义 (支持 JSON 或人类可读格式)
     * @param  JsonRpcProvider|Wallet|null  $runner  Provider 或 Wallet
     */
    public function __construct(
        string $address,
        array|string $abi,
        JsonRpcProvider|Wallet|null $runner = null
    ) {
        parent::__construct($address, $abi, $runner);
    }

    /**
     * 模拟调用 (staticCall)
     * 不会真正发送交易, 用于预检查
     *
     * @param  string  $method  方法名
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数
     * @return mixed 返回值
     */
    public function staticCall(string $method, array $args = [], array $overrides = []): mixed
    {
        return $this->call($method, $args, $overrides);
    }

    /**
     * 批量调用只读方法
     *
     * **重要**: 此方法使用 JSON-RPC 2.0 批量请求特性，将所有调用合并为一次 HTTP 请求
     *
     * @param  array  $calls  调用数组, 每个元素为 ['method' => string, 'args' => array]
     * @return array 返回值数组 (顺序与请求一致)
     *
     * @throws \RuntimeException 当没有 Provider 时
     */
    public function multicall(array $calls): array
    {
        $provider = $this->getProvider();
        if ($provider === null) {
            throw new \RuntimeException('需要 Provider 才能使用 multicall');
        }

        if (empty($calls)) {
            return [];
        }

        // 构建批量请求
        $requests = [];
        foreach ($calls as $call) {
            $func = $this->getFunction($call['method']);
            if ($func === null) {
                throw new \InvalidArgumentException("方法 {$call['method']} 不存在");
            }

            $args = $call['args'] ?? [];
            $data = $this->interface->encodeFunctionData($call['method'], $args);

            $tx = [
                'to' => $this->target,
                'data' => $data,
            ];

            // 如果有 signer, 添加 from
            $signer = $this->getSigner();
            if ($signer !== null) {
                $tx['from'] = $signer->getAddress();
            }

            $requests[] = [
                'method' => 'eth_call',
                'params' => [$tx, 'latest'],
                'context' => ['transaction' => $tx],
            ];
        }

        // 发送批量请求 (一次网络请求)
        $results = $provider->sendBatch($requests);

        // 解码结果
        $decoded = [];
        foreach ($results as $index => $result) {
            if ($result instanceof \Throwable) {
                throw $result;
            }

            $method = $calls[$index]['method'];
            $decoded[] = $this->interface->decodeFunctionResult($method, $result);
        }

        return $decoded;
    }
}
