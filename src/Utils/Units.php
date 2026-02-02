<?php

declare(strict_types=1);

namespace Ethers\Utils;

use InvalidArgumentException;

/**
 * Units 工具类
 * 处理 ETH 和 Wei 之间的转换
 */
class Units
{
    public const WEI = '1';

    public const KWEI = '1000';

    public const MWEI = '1000000';

    public const GWEI = '1000000000';

    public const ETHER = '1000000000000000000';

    /**
     * 将 Ether 转换为 Wei
     */
    public static function parseEther(string $ether): string
    {
        return self::parseUnits($ether, 18);
    }

    /**
     * 将 Wei 转换为 Ether
     */
    public static function formatEther(string $wei): string
    {
        return self::formatUnits($wei, 18);
    }

    /**
     * 将数值按指定精度转换为最小单位
     */
    public static function parseUnits(string $value, int $decimals): string
    {
        // 处理负数
        $negative = str_starts_with($value, '-');
        if ($negative) {
            $value = substr($value, 1);
        }

        // 分离整数和小数部分
        $parts = explode('.', $value);
        $integer = $parts[0] ?? '0';
        $fraction = $parts[1] ?? '';

        // 检查小数位数是否超过精度
        if (strlen($fraction) > $decimals) {
            throw new InvalidArgumentException("小数位数不能超过 {$decimals} 位");
        }

        // 填充小数部分到指定精度
        $fraction = str_pad($fraction, $decimals, '0', STR_PAD_RIGHT);

        // 合并整数和小数部分
        $result = ltrim($integer.$fraction, '0') ?: '0';

        return $negative ? '-'.$result : $result;
    }

    /**
     * 将最小单位按指定精度转换为数值
     */
    public static function formatUnits(string $value, int $decimals): string
    {
        // 处理负数
        $negative = str_starts_with($value, '-');
        if ($negative) {
            $value = substr($value, 1);
        }

        // 确保值长度足够
        $value = str_pad($value, $decimals + 1, '0', STR_PAD_LEFT);

        // 分离整数和小数部分
        $integer = substr($value, 0, -$decimals) ?: '0';
        $fraction = substr($value, -$decimals);

        // 移除尾部的零
        $fraction = rtrim($fraction, '0');

        // 组合结果
        $result = $fraction === '' ? $integer : $integer.'.'.$fraction;

        return $negative ? '-'.$result : $result;
    }

    /**
     * 将 Gwei 转换为 Wei
     */
    public static function parseGwei(string $gwei): string
    {
        return self::parseUnits($gwei, 9);
    }

    /**
     * 将 Wei 转换为 Gwei
     */
    public static function formatGwei(string $wei): string
    {
        return self::formatUnits($wei, 9);
    }
}
