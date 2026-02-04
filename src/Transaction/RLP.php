<?php

declare(strict_types=1);

namespace Ethers\Transaction;

/**
 * RLP
 * Recursive Length Prefix 编码
 *
 * @see https://ethereum.org/en/developers/docs/data-structures-and-encoding/rlp/
 */
class RLP
{
    /**
     * RLP 编码
     *
     * @param  mixed  $input  输入数据 (字符串或数组)
     * @return string 编码后的十六进制字符串 (不带 0x 前缀)
     */
    public static function encode(mixed $input): string
    {
        if (is_array($input)) {
            return self::encodeList($input);
        }

        return self::encodeString($input);
    }

    /**
     * RLP 编码 (支持带 0x 前缀的十六进制字符串)
     *
     * @param  array  $input  输入数组 (十六进制字符串数组)
     * @return string 编码后的十六进制字符串 (带 0x 前缀)
     */
    public static function encodeHex(array $input): string
    {
        $encoded = self::encodeList($input);

        return '0x'.$encoded;
    }

    /**
     * 编码字符串
     */
    private static function encodeString(string $input): string
    {
        // 空字符串
        if ($input === '') {
            return '80';
        }

        // 确保偶数长度的十六进制字符串
        if (strlen($input) % 2 !== 0) {
            $input = '0'.$input;
        }

        // 如果是十六进制字符串, 转为二进制长度
        $bytes = hex2bin($input);
        if ($bytes === false) {
            throw new \InvalidArgumentException('无效的十六进制字符串: '.$input);
        }
        $length = strlen($bytes);

        // 确保 input 是偶数长度 (用于返回)
        $normalizedInput = strlen($input) % 2 !== 0 ? '0'.$input : $input;

        // 单字节且值 < 0x80
        if ($length === 1 && ord($bytes[0]) < 0x80) {
            return $normalizedInput;
        }

        // 短字符串 (长度 < 56)
        if ($length < 56) {
            $prefix = dechex(0x80 + $length);

            return $prefix.$normalizedInput;
        }

        // 长字符串 (长度 >= 56)
        $lengthHex = self::intToHex($length);
        $lengthOfLength = strlen((string) hex2bin($lengthHex));
        $prefix = dechex(0xB7 + $lengthOfLength);

        return $prefix.$lengthHex.$normalizedInput;
    }

    /**
     * 编码列表
     */
    private static function encodeList(array $input): string
    {
        $output = '';

        foreach ($input as $item) {
            $output .= self::encode($item);
        }

        if ($output === '') {
            return 'c0'; // 空列表
        }

        $bytes = hex2bin($output);
        if ($bytes === false) {
            throw new \InvalidArgumentException('无效的列表编码');
        }
        $length = strlen($bytes);

        // 短列表 (长度 < 56)
        if ($length < 56) {
            $prefix = dechex(0xC0 + $length);

            return $prefix.$output;
        }

        // 长列表 (长度 >= 56)
        $lengthHex = self::intToHex($length);
        $lengthOfLength = strlen((string) hex2bin($lengthHex));
        $prefix = dechex(0xF7 + $lengthOfLength);

        return $prefix.$lengthHex.$output;
    }

    /**
     * 整数转十六进制 (最小编码)
     */
    private static function intToHex(int $value): string
    {
        $hex = dechex($value);

        // 确保偶数长度
        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        return $hex;
    }
}
