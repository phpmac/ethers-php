<?php

declare(strict_types=1);

namespace Ethers\Contract;

use Ethers\Utils\Hex;
use Ethers\Utils\Keccak;
use InvalidArgumentException;

/**
 * AbiCoder
 * ABI 编码和解码
 */
class AbiCoder
{
    /**
     * 编码函数调用
     *
     * @param  string  $signature  函数签名, 如 "transfer(address,uint256)"
     * @param  array  $params  参数
     * @return string 编码后的数据
     */
    public function encodeFunctionCall(string $signature, array $params = []): string
    {
        $selector = Keccak::functionSelector($signature);
        $paramTypes = $this->parseSignature($signature);
        $encodedParams = $this->encode($paramTypes, $params);

        return $selector.Hex::stripPrefix($encodedParams);
    }

    /**
     * 编码参数
     *
     * @param  array  $types  类型数组
     * @param  array  $values  值数组
     * @return string 编码后的数据
     */
    public function encode(array $types, array $values): string
    {
        if (count($types) !== count($values)) {
            throw new InvalidArgumentException('类型和值的数量不匹配');
        }

        $headSize = 0;
        $heads = [];
        $tails = [];

        foreach ($types as $i => $type) {
            if ($this->isDynamic($type)) {
                $headSize += 32;
            } else {
                $headSize += 32;
            }
        }

        $tailOffset = $headSize;

        foreach ($types as $i => $type) {
            $encoded = $this->encodeType($type, $values[$i]);

            if ($this->isDynamic($type)) {
                $heads[] = $this->encodeType('uint256', $tailOffset);
                $tails[] = $encoded;
                $tailOffset += strlen(Hex::stripPrefix($encoded)) / 2;
            } else {
                $heads[] = $encoded;
            }
        }

        $result = '0x';
        foreach ($heads as $head) {
            $result .= Hex::stripPrefix($head);
        }
        foreach ($tails as $tail) {
            $result .= Hex::stripPrefix($tail);
        }

        return $result;
    }

    /**
     * 解码参数
     *
     * @param  array  $types  类型数组
     * @param  string  $data  编码的数据
     * @return array 解码后的值
     */
    public function decode(array $types, string $data): array
    {
        $data = Hex::stripPrefix($data);
        $offset = 0;
        $values = [];

        foreach ($types as $type) {
            [$value, $newOffset] = $this->decodeType($type, $data, $offset);
            $values[] = $value;
            $offset = $newOffset;
        }

        return $values;
    }

    /**
     * 编码单个类型
     */
    private function encodeType(string $type, mixed $value): string
    {
        // 处理数组类型
        if (str_ends_with($type, '[]')) {
            return $this->encodeDynamicArray($type, $value);
        }
        if (preg_match('/\[(\d+)\]$/', $type, $matches)) {
            return $this->encodeFixedArray($type, $value, (int) $matches[1]);
        }

        // 基本类型
        if (str_starts_with($type, 'uint')) {
            return $this->encodeInteger($value, false);
        }
        if (str_starts_with($type, 'int')) {
            return $this->encodeInteger($value, true);
        }
        if ($type === 'address') {
            return $this->encodeAddress($value);
        }
        if ($type === 'bool') {
            return $this->encodeBool($value);
        }
        if ($type === 'bytes') {
            return $this->encodeDynamicBytes($value);
        }
        if (preg_match('/^bytes(\d+)$/', $type, $matches)) {
            return $this->encodeFixedBytes($value, (int) $matches[1]);
        }
        if ($type === 'string') {
            return $this->encodeString($value);
        }
        if (str_starts_with($type, 'tuple')) {
            return $this->encodeTuple($type, $value);
        }

        throw new InvalidArgumentException("不支持的类型: {$type}");
    }

    /**
     * 解码单个类型
     */
    private function decodeType(string $type, string $data, int $offset): array
    {
        // 处理数组类型
        if (str_ends_with($type, '[]')) {
            return $this->decodeDynamicArray($type, $data, $offset);
        }
        if (preg_match('/\[(\d+)\]$/', $type, $matches)) {
            return $this->decodeFixedArray($type, $data, $offset, (int) $matches[1]);
        }

        // 基本类型
        if (str_starts_with($type, 'uint')) {
            return $this->decodeUint($data, $offset);
        }
        if (str_starts_with($type, 'int')) {
            return $this->decodeInt($type, $data, $offset);
        }
        if ($type === 'address') {
            return $this->decodeAddress($data, $offset);
        }
        if ($type === 'bool') {
            return $this->decodeBool($data, $offset);
        }
        if ($type === 'bytes') {
            return $this->decodeDynamicBytes($data, $offset);
        }
        if (preg_match('/^bytes(\d+)$/', $type, $matches)) {
            return $this->decodeFixedBytes($data, $offset, (int) $matches[1]);
        }
        if ($type === 'string') {
            return $this->decodeString($data, $offset);
        }

        throw new InvalidArgumentException("不支持的类型: {$type}");
    }

    /**
     * 判断类型是否为动态类型
     */
    private function isDynamic(string $type): bool
    {
        if ($type === 'bytes' || $type === 'string') {
            return true;
        }
        if (str_ends_with($type, '[]')) {
            return true;
        }

        return false;
    }

    /**
     * 解析函数签名获取参数类型
     */
    private function parseSignature(string $signature): array
    {
        preg_match('/\(([^)]*)\)/', $signature, $matches);
        $params = $matches[1] ?? '';

        if ($params === '') {
            return [];
        }

        return array_map('trim', explode(',', $params));
    }

    /**
     * 编码整数 (支持有符号和无符号)
     */
    private function encodeInteger(mixed $value, bool $signed = false): string
    {
        if (is_string($value) && str_starts_with($value, '0x')) {
            $value = Hex::toBigInt($value);
        }

        $value = (string) $value;

        // 处理负数 (二进制补码)
        if ($signed && str_starts_with($value, '-')) {
            $absValue = substr($value, 1);
            // 计算补码: 2^256 - |value|
            $maxValue = bcpow('2', '256');
            $twosComplement = bcsub($maxValue, $absValue);
            $hex = Hex::stripPrefix(Hex::fromBigInt($twosComplement));
        } else {
            $hex = Hex::stripPrefix(Hex::fromBigInt($value));
        }

        return '0x'.str_pad($hex, 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码地址
     */
    private function encodeAddress(string $value): string
    {
        $address = Hex::stripPrefix(strtolower($value));

        return '0x'.str_pad($address, 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码布尔值
     */
    private function encodeBool(bool $value): string
    {
        return '0x'.str_pad($value ? '1' : '0', 64, '0', STR_PAD_LEFT);
    }

    /**
     * 编码固定字节
     */
    private function encodeFixedBytes(string $value, int $length): string
    {
        $hex = Hex::stripPrefix($value);

        return '0x'.str_pad($hex, 64, '0', STR_PAD_RIGHT);
    }

    /**
     * 编码动态字节
     */
    private function encodeDynamicBytes(string $value): string
    {
        $hex = Hex::stripPrefix($value);
        $length = strlen($hex) / 2;
        $paddedLength = ceil(strlen($hex) / 64) * 64;

        return $this->encodeInteger($length).str_pad($hex, (int) $paddedLength, '0', STR_PAD_RIGHT);
    }

    /**
     * 编码字符串
     */
    private function encodeString(string $value): string
    {
        $hex = bin2hex($value);
        $length = strlen($value);
        $paddedLength = ceil(strlen($hex) / 64) * 64;

        return Hex::stripPrefix($this->encodeInteger($length)).str_pad($hex, max((int) $paddedLength, 64), '0', STR_PAD_RIGHT);
    }

    /**
     * 编码动态数组
     */
    private function encodeDynamicArray(string $type, array $values): string
    {
        $baseType = substr($type, 0, -2);
        $length = count($values);

        $encoded = Hex::stripPrefix($this->encodeInteger($length));
        foreach ($values as $value) {
            $encoded .= Hex::stripPrefix($this->encodeType($baseType, $value));
        }

        return '0x'.$encoded;
    }

    /**
     * 编码固定数组
     */
    private function encodeFixedArray(string $type, array $values, int $length): string
    {
        $baseType = preg_replace('/\[\d+\]$/', '', $type);
        $encoded = '';

        foreach ($values as $value) {
            $encoded .= Hex::stripPrefix($this->encodeType($baseType, $value));
        }

        return '0x'.$encoded;
    }

    /**
     * 编码元组
     */
    private function encodeTuple(string $type, array $values): string
    {
        // 简化处理, 元组作为数组处理
        return $this->encode(array_keys($values), array_values($values));
    }

    /**
     * 解码 uint
     */
    private function decodeUint(string $data, int $offset): array
    {
        $chunk = substr($data, $offset * 2, 64);
        $value = Hex::toBigInt('0x'.$chunk);

        return [$value, $offset + 32];
    }

    /**
     * 解码 int
     */
    private function decodeInt(string $type, string $data, int $offset): array
    {
        $chunk = substr($data, $offset * 2, 64);
        // 检查符号位
        $isNegative = hexdec($chunk[0]) >= 8;

        if ($isNegative) {
            // 补码转换
            $inverted = '';
            for ($i = 0; $i < 64; $i++) {
                $inverted .= dechex(15 - hexdec($chunk[$i]));
            }
            $value = '-'.bcadd(Hex::toBigInt('0x'.$inverted), '1');
        } else {
            $value = Hex::toBigInt('0x'.$chunk);
        }

        return [$value, $offset + 32];
    }

    /**
     * 解码地址
     */
    private function decodeAddress(string $data, int $offset): array
    {
        $chunk = substr($data, $offset * 2, 64);
        $address = '0x'.substr($chunk, 24);

        return [strtolower($address), $offset + 32];
    }

    /**
     * 解码布尔值
     */
    private function decodeBool(string $data, int $offset): array
    {
        $chunk = substr($data, $offset * 2, 64);
        $value = Hex::toBigInt('0x'.$chunk) !== '0';

        return [$value, $offset + 32];
    }

    /**
     * 解码固定字节
     */
    private function decodeFixedBytes(string $data, int $offset, int $length): array
    {
        $chunk = substr($data, $offset * 2, 64);
        $value = '0x'.substr($chunk, 0, $length * 2);

        return [$value, $offset + 32];
    }

    /**
     * 解码动态字节
     */
    private function decodeDynamicBytes(string $data, int $offset): array
    {
        // 读取偏移量
        $pointerChunk = substr($data, $offset * 2, 64);
        $pointer = (int) Hex::toBigInt('0x'.$pointerChunk);

        // 读取长度
        $lengthChunk = substr($data, $pointer * 2, 64);
        $length = (int) Hex::toBigInt('0x'.$lengthChunk);

        // 读取数据
        $value = '0x'.substr($data, ($pointer + 32) * 2, $length * 2);

        return [$value, $offset + 32];
    }

    /**
     * 解码字符串
     */
    private function decodeString(string $data, int $offset): array
    {
        // 读取偏移量
        $pointerChunk = substr($data, $offset * 2, 64);
        $pointer = (int) Hex::toBigInt('0x'.$pointerChunk);

        // 读取长度
        $lengthChunk = substr($data, $pointer * 2, 64);
        $length = (int) Hex::toBigInt('0x'.$lengthChunk);

        // 读取数据
        $hex = substr($data, ($pointer + 32) * 2, $length * 2);
        $value = hex2bin($hex);

        return [$value, $offset + 32];
    }

    /**
     * 解码动态数组
     */
    private function decodeDynamicArray(string $type, string $data, int $offset): array
    {
        $baseType = substr($type, 0, -2);

        // 读取偏移量
        $pointerChunk = substr($data, $offset * 2, 64);
        $pointer = (int) Hex::toBigInt('0x'.$pointerChunk);

        // 读取长度
        $lengthChunk = substr($data, $pointer * 2, 64);
        $length = (int) Hex::toBigInt('0x'.$lengthChunk);

        // 读取元素
        $values = [];
        $elementOffset = $pointer + 32;
        for ($i = 0; $i < $length; $i++) {
            [$value, $elementOffset] = $this->decodeType($baseType, $data, $elementOffset);
            $values[] = $value;
        }

        return [$values, $offset + 32];
    }

    /**
     * 解码固定数组
     */
    private function decodeFixedArray(string $type, string $data, int $offset, int $length): array
    {
        $baseType = preg_replace('/\[\d+\]$/', '', $type);

        $values = [];
        $currentOffset = $offset;
        for ($i = 0; $i < $length; $i++) {
            [$value, $currentOffset] = $this->decodeType($baseType, $data, $currentOffset);
            $values[] = $value;
        }

        return [$values, $currentOffset];
    }
}
