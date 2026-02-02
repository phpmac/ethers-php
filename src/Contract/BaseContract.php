<?php

declare(strict_types=1);

namespace Ethers\Contract;

use Ethers\Provider\JsonRpcProvider;
use Ethers\Signer\Wallet;
use InvalidArgumentException;
use RuntimeException;

/**
 * BaseContract
 * 合约基类, 提供更完整的 ethers.js v6 兼容 API
 *
 * 参考 ethers.js v6 的 Contract:
 * - contract.target - 合约地址
 * - contract.runner - Provider 或 Signer
 * - contract.interface - 合约接口
 * - contract.getFunction(name) - 获取函数
 * - contract.getEvent(name) - 获取事件
 * - contract.waitForDeployment() - 等待部署完成
 */
class BaseContract
{
    /**
     * 合约地址 (ethers.js v6 使用 target 属性)
     */
    public readonly string $target;

    /**
     * 合约接口
     */
    public readonly Interface_ $interface;

    /**
     * Runner (Provider 或 Signer)
     */
    protected JsonRpcProvider|Wallet|null $runner;

    /**
     * 部署交易哈希
     */
    protected ?string $deploymentTxHash = null;

    /**
     * 部署交易回执
     */
    protected ?array $deploymentReceipt = null;

    /**
     * 已解析的函数绑定
     */
    protected array $functions = [];

    /**
     * 构造函数
     *
     * @param  string  $address  合约地址
     * @param  array|string  $abi  ABI 定义
     * @param  JsonRpcProvider|Wallet|null  $runner  Provider 或 Wallet
     */
    public function __construct(
        string $address,
        array|string $abi,
        JsonRpcProvider|Wallet|null $runner = null
    ) {
        $this->target = strtolower($address);
        $this->interface = new Interface_($abi);
        $this->runner = $runner;
        $this->buildFunctions();
    }

    /**
     * 构建函数绑定
     */
    protected function buildFunctions(): void
    {
        foreach ($this->interface->getAllFunctions() as $name => $func) {
            $this->functions[$name] = new ContractFunction($this, $func);
        }
    }

    /**
     * 获取合约地址 (兼容 ethers.js v6)
     */
    public function getAddress(): string
    {
        return $this->target;
    }

    /**
     * 获取接口
     */
    public function getInterface(): Interface_
    {
        return $this->interface;
    }

    /**
     * 获取 Runner
     */
    public function getRunner(): JsonRpcProvider|Wallet|null
    {
        return $this->runner;
    }

    /**
     * 获取 Provider
     */
    public function getProvider(): ?JsonRpcProvider
    {
        if ($this->runner instanceof JsonRpcProvider) {
            return $this->runner;
        }
        if ($this->runner instanceof Wallet) {
            return $this->runner->getProvider();
        }

        return null;
    }

    /**
     * 获取 Signer
     */
    public function getSigner(): ?Wallet
    {
        if ($this->runner instanceof Wallet) {
            return $this->runner;
        }

        return null;
    }

    /**
     * 连接新的 runner
     *
     * @return static 新的合约实例
     */
    public function connect(JsonRpcProvider|Wallet $runner): static
    {
        return new static($this->target, $this->interface->getAbi(), $runner);
    }

    /**
     * 设置部署交易信息
     *
     * @internal 仅供 ContractFactory 使用
     */
    public function setDeploymentTransaction(string $hash, array $receipt): void
    {
        $this->deploymentTxHash = $hash;
        $this->deploymentReceipt = $receipt;
    }

    /**
     * 获取部署交易
     */
    public function deploymentTransaction(): ?array
    {
        if ($this->deploymentTxHash === null) {
            return null;
        }

        return [
            'hash' => $this->deploymentTxHash,
            'receipt' => $this->deploymentReceipt,
        ];
    }

    /**
     * 等待部署完成 (合约已部署时立即返回)
     */
    public function waitForDeployment(): static
    {
        // 如果有部署交易但没有收据, 等待确认
        if ($this->deploymentTxHash !== null && $this->deploymentReceipt === null) {
            $provider = $this->getProvider();
            if ($provider !== null) {
                $this->deploymentReceipt = $provider->waitForTransaction($this->deploymentTxHash);
            }
        }

        // 验证合约代码存在
        $provider = $this->getProvider();
        if ($provider !== null) {
            $code = $provider->getCode($this->target);
            if ($code === '0x' || $code === '') {
                throw new RuntimeException('合约部署失败: 目标地址没有代码');
            }
        }

        return $this;
    }

    /**
     * 获取函数
     *
     * @param  string  $name  函数名或签名
     */
    public function getFunction(string $name): ?ContractFunction
    {
        return $this->functions[$name] ?? null;
    }

    /**
     * 获取事件
     *
     * @param  string  $name  事件名
     */
    public function getEvent(string $name): ?array
    {
        return $this->interface->getEvent($name);
    }

    /**
     * 调用只读方法 (staticCall / call)
     *
     * @param  string  $method  方法名
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数
     * @return mixed 返回值
     */
    public function call(string $method, array $args = [], array $overrides = []): mixed
    {
        $func = $this->getFunction($method);
        if ($func === null) {
            throw new InvalidArgumentException("方法 {$method} 不存在");
        }

        return $func->staticCall($args, $overrides);
    }

    /**
     * 发送交易调用方法
     *
     * @param  string  $method  方法名
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数 (value, gasLimit, etc.)
     * @return array{hash: string, wait: callable}
     */
    public function send(string $method, array $args = [], array $overrides = []): array
    {
        $func = $this->getFunction($method);
        if ($func === null) {
            throw new InvalidArgumentException("方法 {$method} 不存在");
        }

        return $func->send($args, $overrides);
    }

    /**
     * 估算 gas
     *
     * @param  string  $method  方法名
     * @param  array  $args  参数
     * @param  array  $overrides  覆盖参数
     * @return string gas 数量 (BigInt 字符串)
     */
    public function estimateGas(string $method, array $args = [], array $overrides = []): string
    {
        $func = $this->getFunction($method);
        if ($func === null) {
            throw new InvalidArgumentException("方法 {$method} 不存在");
        }

        return $func->estimateGas($args, $overrides);
    }

    /**
     * 获取函数调用数据 (不发送交易)
     *
     * @param  string  $method  方法名
     * @param  array  $args  参数
     * @return string 编码后的数据
     */
    public function encodeFunction(string $method, array $args = []): string
    {
        return $this->interface->encodeFunctionData($method, $args);
    }

    /**
     * 解码函数返回数据
     *
     * @param  string  $method  方法名
     * @param  string  $data  返回数据
     */
    public function decodeResult(string $method, string $data): array
    {
        return $this->interface->decodeFunctionResult($method, $data);
    }

    /**
     * 获取事件日志
     *
     * @param  string  $eventName  事件名
     * @param  array  $filter  过滤条件
     */
    public function queryFilter(string $eventName, array $filter = []): array
    {
        $provider = $this->getProvider();
        if ($provider === null) {
            throw new RuntimeException('需要 Provider 才能查询事件');
        }

        $event = $this->interface->getEvent($eventName);
        if ($event === null) {
            throw new InvalidArgumentException("事件 {$eventName} 不存在");
        }

        $logFilter = array_merge([
            'address' => $this->target,
            'topics' => [$event['topic']],
        ], $filter);

        $logs = $provider->getLogs($logFilter);
        $decoded = [];

        foreach ($logs as $log) {
            $decoded[] = $this->interface->decodeEventLog($log);
        }

        return $decoded;
    }

    /**
     * 魔术方法, 允许直接调用合约方法
     *
     * @param  string  $name  方法名
     * @param  array  $arguments  参数
     */
    public function __call(string $name, array $arguments): mixed
    {
        $func = $this->getFunction($name);
        if ($func === null) {
            throw new InvalidArgumentException("方法 {$name} 不存在");
        }

        // 最后一个参数可能是 overrides
        $overrides = [];
        if (! empty($arguments) && is_array(end($arguments))) {
            $lastArg = end($arguments);
            if (isset($lastArg['value']) || isset($lastArg['gas']) || isset($lastArg['gasLimit']) || isset($lastArg['from'])) {
                $overrides = array_pop($arguments);
            }
        }

        // 根据 stateMutability 决定调用方式
        if (in_array($func->getStateMutability(), ['view', 'pure'])) {
            return $func->staticCall($arguments, $overrides);
        }

        // 需要发送交易
        return $func->send($arguments, $overrides);
    }

    /**
     * 魔术方法, 获取函数属性
     */
    public function __get(string $name): mixed
    {
        if (isset($this->functions[$name])) {
            return $this->functions[$name];
        }

        throw new InvalidArgumentException("属性 {$name} 不存在");
    }
}
