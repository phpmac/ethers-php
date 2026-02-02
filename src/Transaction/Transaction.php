<?php

declare(strict_types=1);

namespace Ethers\Transaction;

use Ethers\Utils\Hex;
use kornrunner\Secp256k1;

/**
 * Transaction
 * 以太坊交易类, 支持 Legacy 和 EIP-1559 交易
 */
class Transaction
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * 签名交易
     *
     * @param  string  $privateKey  私钥 (不带 0x 前缀)
     * @return string 签名后的交易数据 (带 0x 前缀)
     */
    public function sign(string $privateKey): string
    {
        $privateKey = Hex::stripPrefix($privateKey);

        // 判断交易类型
        if (isset($this->data['maxFeePerGas'])) {
            return $this->signEip1559($privateKey);
        }

        return $this->signLegacy($privateKey);
    }

    /**
     * 签名 Legacy 交易 (EIP-155)
     */
    private function signLegacy(string $privateKey): string
    {
        $chainId = $this->data['chainId'];

        // 构建待签名的 RLP 数据 (包含 chainId, 0, 0 用于 EIP-155)
        $rawTx = [
            $this->formatValue($this->data['nonce'] ?? 0),
            $this->formatValue($this->data['gasPrice'] ?? 0),
            $this->formatValue($this->data['gasLimit'] ?? 21000),
            $this->formatAddress($this->data['to'] ?? ''),
            $this->formatValue($this->data['value'] ?? 0),
            $this->formatData($this->data['data'] ?? ''),
            $this->formatValue($chainId),
            '',
            '',
        ];

        $rlpEncoded = RLP::encode($rawTx);
        $hash = \kornrunner\Keccak::hash(hex2bin($rlpEncoded), 256);

        // 签名
        $secp256k1 = new Secp256k1;
        $signature = $secp256k1->sign($hash, $privateKey);

        $r = str_pad(gmp_strval($signature->getR(), 16), 64, '0', STR_PAD_LEFT);
        $s = str_pad(gmp_strval($signature->getS(), 16), 64, '0', STR_PAD_LEFT);
        $v = $signature->getRecoveryParam() + 35 + ($chainId * 2);

        // 构建签名后的交易
        $signedTx = [
            $this->formatValue($this->data['nonce'] ?? 0),
            $this->formatValue($this->data['gasPrice'] ?? 0),
            $this->formatValue($this->data['gasLimit'] ?? 21000),
            $this->formatAddress($this->data['to'] ?? ''),
            $this->formatValue($this->data['value'] ?? 0),
            $this->formatData($this->data['data'] ?? ''),
            $this->formatValue($v),
            $r,
            $s,
        ];

        return '0x'.RLP::encode($signedTx);
    }

    /**
     * 签名 EIP-1559 交易
     */
    private function signEip1559(string $privateKey): string
    {
        $chainId = $this->data['chainId'];

        // EIP-1559 交易字段
        $rawTx = [
            $this->formatValue($chainId),
            $this->formatValue($this->data['nonce'] ?? 0),
            $this->formatValue($this->data['maxPriorityFeePerGas'] ?? 0),
            $this->formatValue($this->data['maxFeePerGas'] ?? 0),
            $this->formatValue($this->data['gasLimit'] ?? 21000),
            $this->formatAddress($this->data['to'] ?? ''),
            $this->formatValue($this->data['value'] ?? 0),
            $this->formatData($this->data['data'] ?? ''),
            [], // accessList
        ];

        $rlpEncoded = RLP::encode($rawTx);
        // EIP-1559 交易前缀 0x02
        $toSign = '02'.$rlpEncoded;
        $hash = \kornrunner\Keccak::hash(hex2bin($toSign), 256);

        // 签名
        $secp256k1 = new Secp256k1;
        $signature = $secp256k1->sign($hash, $privateKey);

        $r = str_pad(gmp_strval($signature->getR(), 16), 64, '0', STR_PAD_LEFT);
        $s = str_pad(gmp_strval($signature->getS(), 16), 64, '0', STR_PAD_LEFT);
        $v = $signature->getRecoveryParam();

        // 构建签名后的交易
        $signedTx = [
            $this->formatValue($chainId),
            $this->formatValue($this->data['nonce'] ?? 0),
            $this->formatValue($this->data['maxPriorityFeePerGas'] ?? 0),
            $this->formatValue($this->data['maxFeePerGas'] ?? 0),
            $this->formatValue($this->data['gasLimit'] ?? 21000),
            $this->formatAddress($this->data['to'] ?? ''),
            $this->formatValue($this->data['value'] ?? 0),
            $this->formatData($this->data['data'] ?? ''),
            [], // accessList
            $this->formatValue($v),
            $r,
            $s,
        ];

        // EIP-1559 交易前缀 0x02
        return '0x02'.RLP::encode($signedTx);
    }

    /**
     * 格式化数值为无前缀十六进制
     */
    private function formatValue(mixed $value): string
    {
        if (is_string($value) && str_starts_with($value, '0x')) {
            $hex = Hex::stripPrefix($value);

            return ltrim($hex, '0') ?: '';
        }

        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            $int = (int) $value;
            if ($int === 0) {
                return '';
            }

            return dechex($int);
        }

        return '';
    }

    /**
     * 格式化地址
     */
    private function formatAddress(string $address): string
    {
        if ($address === '') {
            return '';
        }

        return strtolower(Hex::stripPrefix($address));
    }

    /**
     * 格式化数据
     */
    private function formatData(string $data): string
    {
        if ($data === '' || $data === '0x') {
            return '';
        }

        return Hex::stripPrefix($data);
    }
}
