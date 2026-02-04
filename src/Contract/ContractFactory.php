<?php

declare(strict_types=1);

namespace Ethers\Contract;

use Ethers\Ethers;
use Ethers\Signer\Wallet;
use Ethers\Utils\Hex;
use InvalidArgumentException;
use RuntimeException;

/**
 * ContractFactory
 * 合约工厂类, 用于部署智能合约
 *
 * 参考 ethers.js v6 的 ContractFactory:
 *
 * $factory = new ContractFactory($abi, $bytecode, $signer);
 * $contract = $factory->deploy($arg1, $arg2);
 * $contract->waitForDeployment();
 */
class ContractFactory
{
    private Interface_ $interface;

    private string $bytecode;

    private ?Wallet $runner;

    /**
     * 构造函数
     *
     * @param  array|string  $abi  ABI 定义 (支持 JSON 或人类可读格式)
     * @param  string  $bytecode  合约字节码 (带或不带 0x 前缀)
     * @param  Wallet|null  $runner  签名器
     */
    public function __construct(
        array|string $abi,
        string $bytecode,
        ?Wallet $runner = null
    ) {
        $this->interface = new Interface_($abi);
        $this->bytecode = Hex::prefix($bytecode);
        $this->runner = $runner;
    }

    /**
     * 获取接口
     */
    public function getInterface(): Interface_
    {
        return $this->interface;
    }

    /**
     * 获取字节码
     */
    public function getBytecode(): string
    {
        return $this->bytecode;
    }

    /**
     * 获取 Runner (Signer)
     */
    public function getRunner(): ?Wallet
    {
        return $this->runner;
    }

    /**
     * 连接新的 Signer
     *
     * @return self 新的 ContractFactory 实例
     */
    public function connect(Wallet $runner): self
    {
        return new self($this->interface->getAbi(), $this->bytecode, $runner);
    }

    /**
     * 部署合约
     *
     * @param  mixed  ...$args  构造函数参数, 最后一个参数可以是 overrides 数组
     * @return BaseContract 已部署的合约实例 (包含 deploymentTransaction)
     *
     * @throws RuntimeException
     */
    public function deploy(mixed ...$args): BaseContract
    {
        if ($this->runner === null) {
            throw new RuntimeException('需要 Signer 才能部署合约');
        }

        $provider = $this->runner->getProvider();
        if ($provider === null) {
            throw new RuntimeException('Signer 需要连接 Provider');
        }

        // 分离 overrides
        $overrides = [];
        if (! empty($args) && is_array(end($args))) {
            $lastArg = end($args);
            if (isset($lastArg['value']) || isset($lastArg['gas']) || isset($lastArg['gasLimit']) || isset($lastArg['gasPrice']) || isset($lastArg['maxFeePerGas'])) {
                $overrides = array_pop($args);
            }
        }

        // 编码构造函数参数
        $data = $this->bytecode;
        $constructor = $this->interface->getConstructor();
        if ($constructor !== null && ! empty($constructor['inputs'])) {
            if (count($args) !== count($constructor['inputs'])) {
                throw new InvalidArgumentException(
                    sprintf('构造函数需要 %d 个参数, 但提供了 %d 个', count($constructor['inputs']), count($args))
                );
            }
            $encodedArgs = $this->interface->encodeDeploy($args);
            $data .= Hex::stripPrefix($encodedArgs);
        }

        // 获取当前 nonce 以计算合约地址
        $nonce = $this->runner->getNonce();

        // 使用 getCreateAddress 计算合约地址
        $contractAddress = Ethers::getCreateAddress(
            $this->runner->getAddress(),
            $nonce
        );

        // 构建交易
        $tx = array_merge([
            'data' => $data,
        ], $overrides);

        // 发送交易
        $response = $this->runner->sendTransaction($tx);
        $hash = $response['hash'];

        // 等待交易确认
        $receipt = $response['wait'](1, 120);

        // 创建合约实例
        $contract = new BaseContract(
            $contractAddress,
            $this->interface->getAbi(),
            $this->runner
        );

        // 设置部署交易信息
        $contract->setDeploymentTransaction($hash, $receipt);

        return $contract;
    }

    /**
     * 获取部署交易数据 (不实际部署)
     *
     * @param  mixed  ...$args  构造函数参数
     * @return string 部署交易数据
     */
    public function getDeployTransaction(mixed ...$args): string
    {
        $data = $this->bytecode;
        $constructor = $this->interface->getConstructor();

        if ($constructor !== null && ! empty($constructor['inputs'])) {
            if (count($args) !== count($constructor['inputs'])) {
                throw new InvalidArgumentException(
                    sprintf('构造函数需要 %d 个参数, 但提供了 %d 个', count($constructor['inputs']), count($args))
                );
            }
            $encodedArgs = $this->interface->encodeDeploy($args);
            $data .= Hex::stripPrefix($encodedArgs);
        }

        return $data;
    }

    /**
     * 从已部署的合约获取 attached 合约实例
     *
     * @param  string  $address  已部署的合约地址
     */
    public function attach(string $address): Contract
    {
        return new Contract($address, $this->interface->getAbi(), $this->runner);
    }
}
