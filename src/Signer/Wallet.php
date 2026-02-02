<?php

declare(strict_types=1);

namespace Ethers\Signer;

use Elliptic\EC;
use Ethers\Provider\JsonRpcProvider;
use Ethers\Transaction\Transaction;
use Ethers\Utils\Hex;
use Ethers\Utils\Keccak;
use kornrunner\Secp256k1;
use RuntimeException;

/**
 * Wallet
 * 钱包类, 用于签名交易
 *
 * 参考 ethers.js v6 的 Wallet
 */
class Wallet
{
    private string $privateKey;

    private string $address;

    private ?JsonRpcProvider $provider;

    /**
     * 构造函数
     *
     * @param  string  $privateKey  私钥 (带或不带 0x 前缀)
     * @param  JsonRpcProvider|null  $provider  可选的 Provider
     */
    public function __construct(string $privateKey, ?JsonRpcProvider $provider = null)
    {
        $this->privateKey = Hex::stripPrefix($privateKey);
        $this->address = $this->deriveAddress($this->privateKey);
        $this->provider = $provider;
    }

    /**
     * 从私钥派生地址
     */
    private function deriveAddress(string $privateKey): string
    {
        // 使用 elliptic-php 获取公钥
        $ec = new EC('secp256k1');
        $keyPair = $ec->keyFromPrivate($privateKey, 'hex');
        $publicKey = $keyPair->getPublic(false, 'hex');

        // 移除 04 前缀 (非压缩公钥标识)
        $publicKey = substr($publicKey, 2);

        // Keccak256 哈希公钥
        $hash = \kornrunner\Keccak::hash(hex2bin($publicKey), 256);

        // 取后 20 字节 (40 个十六进制字符) 作为地址
        $address = '0x'.substr($hash, -40);

        return strtolower($address);
    }

    /**
     * 获取地址
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * 获取私钥
     */
    public function getPrivateKey(): string
    {
        return '0x'.$this->privateKey;
    }

    /**
     * 连接 Provider
     *
     * @return self 返回新的 Wallet 实例
     */
    public function connect(JsonRpcProvider $provider): self
    {
        return new self($this->privateKey, $provider);
    }

    /**
     * 获取 Provider
     */
    public function getProvider(): ?JsonRpcProvider
    {
        return $this->provider;
    }

    /**
     * 获取余额
     *
     * @return string Wei (BigInt 字符串)
     *
     * @throws RuntimeException
     */
    public function getBalance(): string
    {
        if ($this->provider === null) {
            throw new RuntimeException('需要连接 Provider 才能获取余额');
        }

        return $this->provider->getBalance($this->address);
    }

    /**
     * 获取交易计数 (nonce)
     *
     * @throws RuntimeException
     */
    public function getNonce(string $blockTag = 'pending'): int
    {
        if ($this->provider === null) {
            throw new RuntimeException('需要连接 Provider 才能获取 nonce');
        }

        return $this->provider->getTransactionCount($this->address, $blockTag);
    }

    /**
     * 签名消息
     *
     * @param  string  $message  消息
     * @return string 签名
     */
    public function signMessage(string $message): string
    {
        // 添加以太坊签名前缀
        $prefix = "\x19Ethereum Signed Message:\n".strlen($message);
        $hash = Keccak::hash($prefix.$message);

        return $this->signHash($hash);
    }

    /**
     * 签名哈希
     *
     * @param  string  $hash  32字节哈希
     * @return string 签名
     */
    public function signHash(string $hash): string
    {
        $secp256k1 = new Secp256k1;
        $signature = $secp256k1->sign(Hex::stripPrefix($hash), $this->privateKey);

        $r = str_pad(gmp_strval($signature->getR(), 16), 64, '0', STR_PAD_LEFT);
        $s = str_pad(gmp_strval($signature->getS(), 16), 64, '0', STR_PAD_LEFT);
        $v = dechex($signature->getRecoveryParam() + 27);

        return '0x'.$r.$s.$v;
    }

    /**
     * 签名交易
     *
     * @param  array  $transaction  交易对象
     * @return string 已签名的交易数据
     */
    public function signTransaction(array $transaction): string
    {
        // 确保必要字段
        if (! isset($transaction['chainId'])) {
            if ($this->provider !== null) {
                $transaction['chainId'] = $this->provider->getChainId();
            } else {
                throw new RuntimeException('交易必须包含 chainId');
            }
        }

        // 格式化交易字段
        $txData = [
            'nonce' => $this->formatHex($transaction['nonce'] ?? 0),
            'to' => $transaction['to'] ?? '',
            'value' => $this->formatHex($transaction['value'] ?? 0),
            'data' => $transaction['data'] ?? '0x',
            'chainId' => $transaction['chainId'],
        ];

        // EIP-1559 交易
        if (isset($transaction['maxFeePerGas'])) {
            $txData['maxPriorityFeePerGas'] = $this->formatHex($transaction['maxPriorityFeePerGas'] ?? 0);
            $txData['maxFeePerGas'] = $this->formatHex($transaction['maxFeePerGas']);
            $txData['gasLimit'] = $this->formatHex($transaction['gasLimit'] ?? $transaction['gas'] ?? 21000);
        } else {
            // Legacy 交易
            $txData['gasPrice'] = $this->formatHex($transaction['gasPrice'] ?? 0);
            $txData['gasLimit'] = $this->formatHex($transaction['gasLimit'] ?? $transaction['gas'] ?? 21000);
        }

        $tx = new Transaction($txData);

        return $tx->sign($this->privateKey);
    }

    /**
     * 发送交易
     *
     * @param  array  $transaction  交易对象
     * @return array{hash: string, wait: callable} 交易响应
     *
     * @throws RuntimeException
     */
    public function sendTransaction(array $transaction): array
    {
        if ($this->provider === null) {
            throw new RuntimeException('需要连接 Provider 才能发送交易');
        }

        // 自动填充 from
        $transaction['from'] = $this->address;

        // 自动填充 nonce
        if (! isset($transaction['nonce'])) {
            $transaction['nonce'] = $this->getNonce();
        }

        // 自动填充 gas
        if (! isset($transaction['gas']) && ! isset($transaction['gasLimit'])) {
            $transaction['gas'] = $this->provider->estimateGas($transaction);
        }

        // 自动填充 gas price
        if (! isset($transaction['gasPrice']) && ! isset($transaction['maxFeePerGas'])) {
            $feeData = $this->provider->getFeeData();
            if ($feeData['maxFeePerGas'] !== null) {
                $transaction['maxFeePerGas'] = $feeData['maxFeePerGas'];
                $transaction['maxPriorityFeePerGas'] = $feeData['maxPriorityFeePerGas'];
            } else {
                $transaction['gasPrice'] = $feeData['gasPrice'];
            }
        }

        // 签名并发送
        $signedTx = $this->signTransaction($transaction);
        $hash = $this->provider->sendRawTransaction($signedTx);

        $provider = $this->provider;

        return [
            'hash' => $hash,
            'wait' => function (int $confirmations = 1, int $timeout = 60) use ($provider, $hash) {
                return $provider->waitForTransaction($hash, $confirmations, $timeout);
            },
        ];
    }

    /**
     * 格式化为十六进制
     */
    private function formatHex(mixed $value): string
    {
        if (is_string($value) && str_starts_with($value, '0x')) {
            return $value;
        }

        if (is_int($value)) {
            return '0x'.dechex($value);
        }

        if (is_string($value) && is_numeric($value)) {
            return Hex::fromBigInt($value);
        }

        return '0x0';
    }

    /**
     * 创建随机钱包
     */
    public static function createRandom(?JsonRpcProvider $provider = null): self
    {
        $privateKey = bin2hex(random_bytes(32));

        return new self($privateKey, $provider);
    }
}
