<?php

declare(strict_types=1);

namespace Ethers\Utils;

/**
 * Hex 工具类
 * 处理十六进制字符串的编码和解码
 */
class Hex
{
    /**
     * 确保字符串有 0x 前缀
     */
    public static function prefix(string $hex): string
    {
        if (str_starts_with($hex, '0x') || str_starts_with($hex, '0X')) {
            return '0x'.substr($hex, 2);
        }

        return '0x'.$hex;
    }

    /**
     * 移除 0x 前缀
     */
    public static function stripPrefix(string $hex): string
    {
        if (str_starts_with($hex, '0x') || str_starts_with($hex, '0X')) {
            return substr($hex, 2);
        }

        return $hex;
    }

    /**
     * 验证是否为有效的十六进制字符串
     */
    public static function isValid(string $hex): bool
    {
        $stripped = self::stripPrefix($hex);

        return ctype_xdigit($stripped) || $stripped === '';
    }

    /**
     * 从整数转换为十六进制
     */
    public static function fromInt(int|string $value): string
    {
        if (is_string($value)) {
            $value = (int) $value;
        }

        return '0x'.dechex($value);
    }

    /**
     * 从十六进制转换为整数
     */
    public static function toInt(string $hex): int
    {
        return (int) hexdec(self::stripPrefix($hex));
    }

    /**
     * 从十六进制转换为 BigInt 字符串
     */
    public static function toBigInt(string $hex): string
    {
        $hex = self::stripPrefix($hex);
        if ($hex === '' || $hex === '0') {
            return '0';
        }

        $result = '0';
        $base = '1';

        for ($i = strlen($hex) - 1; $i >= 0; $i--) {
            $digit = hexdec($hex[$i]);
            $result = bcadd($result, bcmul((string) $digit, $base));
            $base = bcmul($base, '16');
        }

        return $result;
    }

    /**
     * 从 BigInt 字符串转换为十六进制
     */
    public static function fromBigInt(string $value): string
    {
        if ($value === '0') {
            return '0x0';
        }

        $hex = '';
        while (bccomp($value, '0') > 0) {
            $remainder = bcmod($value, '16');
            $hex = dechex((int) $remainder).$hex;
            $value = bcdiv($value, '16', 0);
        }

        return '0x'.$hex;
    }

    /**
     * 从字节数组转换为十六进制
     */
    public static function fromBytes(array $bytes): string
    {
        $hex = '';
        foreach ($bytes as $byte) {
            $hex .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
        }

        return '0x'.$hex;
    }

    /**
     * 从十六进制转换为字节数组
     */
    public static function toBytes(string $hex): array
    {
        $hex = self::stripPrefix($hex);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        $bytes = [];
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $bytes[] = hexdec(substr($hex, $i, 2));
        }

        return $bytes;
    }

    /**
     * 填充十六进制到指定长度
     */
    public static function padLeft(string $hex, int $length): string
    {
        $stripped = self::stripPrefix($hex);

        return '0x'.str_pad($stripped, $length, '0', STR_PAD_LEFT);
    }

    /**
     * 填充十六进制到指定长度 (右侧)
     */
    public static function padRight(string $hex, int $length): string
    {
        $stripped = self::stripPrefix($hex);

        return '0x'.str_pad($stripped, $length, '0', STR_PAD_RIGHT);
    }
}
