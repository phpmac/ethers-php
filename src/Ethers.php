<?php

declare(strict_types=1);

namespace Ethers;

use Ethers\Contract\AbiCoder;
use Ethers\Contract\Contract;
use Ethers\Contract\ContractFactory;
use Ethers\Contract\Interface_;
use Ethers\Provider\JsonRpcProvider;
use Ethers\Signer\Wallet;
use Ethers\Utils\Hex;
use Ethers\Utils\Keccak;
use Ethers\Utils\Units;

/**
 * Ethers
 * 主入口类, 提供静态方法访问所有功能
 *
 * 使用方式类似 ethers.js v6:
 *
 * // Provider
 * $provider = Ethers::getDefaultProvider('https://rpc.example.com');
 *
 * // Wallet
 * $wallet = Ethers::wallet($privateKey, $provider);
 *
 * // Contract (读取/写入)
 * $contract = Ethers::contract($address, $abi, $wallet);
 * $balance = $contract->balanceOf($address);
 * $tx = $contract->transfer($to, $amount);
 *
 * // 部署合约
 * $factory = Ethers::contractFactory($abi, $bytecode, $wallet);
 * $contract = $factory->deploy($arg1, $arg2);
 *
 * // 人类可读 ABI
 * $contract = Ethers::contract($address, [
 *     "function name() view returns (string)",
 *     "function transfer(address to, uint256 amount) returns (bool)",
 * ], $wallet);
 */
class Ethers
{
    /**
     * 创建 JsonRpcProvider
     */
    public static function getDefaultProvider(string $url, array $options = []): JsonRpcProvider
    {
        return new JsonRpcProvider($url, $options);
    }

    /**
     * 创建 Wallet
     */
    public static function wallet(string $privateKey, ?JsonRpcProvider $provider = null): Wallet
    {
        return new Wallet($privateKey, $provider);
    }

    /**
     * 创建 Contract
     */
    public static function contract(string $address, array|string $abi, JsonRpcProvider|Wallet|null $runner = null): Contract
    {
        return new Contract($address, $abi, $runner);
    }

    /**
     * 创建 Interface
     */
    public static function interface(array|string $abi): Interface_
    {
        return new Interface_($abi);
    }

    /**
     * 创建 AbiCoder
     */
    public static function abiCoder(): AbiCoder
    {
        return new AbiCoder;
    }

    /**
     * 创建 ContractFactory (用于部署合约)
     *
     * @param  array|string  $abi  ABI 定义 (支持 JSON 或人类可读格式)
     * @param  string  $bytecode  合约字节码
     * @param  Wallet|null  $runner  签名器
     */
    public static function contractFactory(array|string $abi, string $bytecode, ?Wallet $runner = null): ContractFactory
    {
        return new ContractFactory($abi, $bytecode, $runner);
    }

    /**
     * 创建随机 Wallet
     */
    public static function createRandomWallet(?JsonRpcProvider $provider = null): Wallet
    {
        return Wallet::createRandom($provider);
    }

    /**
     * 从人类可读 ABI 创建 Interface
     *
     * @param  array  $fragments  人类可读 ABI 片段
     */
    public static function parseAbi(array $fragments): Interface_
    {
        return Interface_::from($fragments);
    }

    // ==================== 工具方法 ====================

    /**
     * 解析 Ether 为 Wei
     */
    public static function parseEther(string $ether): string
    {
        return Units::parseEther($ether);
    }

    /**
     * 格式化 Wei 为 Ether
     */
    public static function formatEther(string $wei): string
    {
        return Units::formatEther($wei);
    }

    /**
     * 解析指定精度
     */
    public static function parseUnits(string $value, int $decimals): string
    {
        return Units::parseUnits($value, $decimals);
    }

    /**
     * 格式化指定精度
     */
    public static function formatUnits(string $value, int $decimals): string
    {
        return Units::formatUnits($value, $decimals);
    }

    /**
     * Keccak256 哈希
     */
    public static function keccak256(string $data): string
    {
        return Keccak::hash($data);
    }

    /**
     * 函数选择器
     */
    public static function id(string $signature): string
    {
        return Keccak::functionSelector($signature);
    }

    /**
     * 检查是否为有效地址
     */
    public static function isAddress(string $address): bool
    {
        if (! preg_match('/^0x[0-9a-fA-F]{40}$/', $address)) {
            return false;
        }

        return true;
    }

    /**
     * 获取校验和地址
     */
    public static function getAddress(string $address): string
    {
        if (! self::isAddress($address)) {
            throw new \InvalidArgumentException('无效的地址');
        }

        $address = strtolower(substr($address, 2));
        $hash = Keccak::hash($address);
        $hash = substr($hash, 2);

        $checksumAddress = '0x';
        for ($i = 0; $i < 40; $i++) {
            if (hexdec($hash[$i]) >= 8) {
                $checksumAddress .= strtoupper($address[$i]);
            } else {
                $checksumAddress .= $address[$i];
            }
        }

        return $checksumAddress;
    }

    /**
     * 零地址
     */
    public static function zeroAddress(): string
    {
        return '0x0000000000000000000000000000000000000000';
    }

    /**
     * 零哈希
     */
    public static function zeroHash(): string
    {
        return '0x0000000000000000000000000000000000000000000000000000000000000000';
    }

    /**
     * 十六进制工具
     */
    public static function hex(): Hex
    {
        return new Hex;
    }

    /**
     * 从 BigInt 转为 Hex
     */
    public static function toBeHex(string $value): string
    {
        return Hex::fromBigInt($value);
    }

    /**
     * 从 Hex 转为 BigInt
     */
    public static function toBigInt(string $hex): string
    {
        return Hex::toBigInt($hex);
    }
}
