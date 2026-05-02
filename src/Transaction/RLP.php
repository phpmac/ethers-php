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
        if ($input === '') {
            return '80';
        }

        if (strlen($input) % 2 !== 0) {
            $input = '0'.$input;
        }

        $length = strlen($input) / 2;

        if ($length === 1 && hexdec($input) < 0x80) {
            return $input;
        }

        if ($length < 56) {
            $prefix = dechex(0x80 + $length);

            return $prefix.$input;
        }

        $lengthHex = self::intToHex($length);
        $lengthOfLength = strlen($lengthHex) / 2;
        $prefix = dechex(0xB7 + $lengthOfLength);

        return $prefix.$lengthHex.$input;
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
            return 'c0';
        }

        $length = strlen($output) / 2;

        if ($length < 56) {
            $prefix = dechex(0xC0 + $length);

            return $prefix.$output;
        }

        $lengthHex = self::intToHex($length);
        $lengthOfLength = strlen($lengthHex) / 2;
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
