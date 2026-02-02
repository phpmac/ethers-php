<?php

declare(strict_types=1);

namespace Tests\Ethers\Utils;

use Ethers\Utils\Units;
use PHPUnit\Framework\TestCase;

/**
 * Units 单位转换测试
 */
class UnitsTest extends TestCase
{
    /**
     * 测试 Ether 转 Wei
     */
    public function test_parse_ether(): void
    {
        $this->assertEquals('1000000000000000000', Units::parseEther('1'));
        $this->assertEquals('1500000000000000000', Units::parseEther('1.5'));
        $this->assertEquals('100000000000000000', Units::parseEther('0.1'));
        $this->assertEquals('10000000000000000000', Units::parseEther('10'));
    }

    /**
     * 测试 Wei 转 Ether
     */
    public function test_format_ether(): void
    {
        $this->assertEquals('1', Units::formatEther('1000000000000000000'));
        $this->assertEquals('1.5', Units::formatEther('1500000000000000000'));
        $this->assertEquals('0.1', Units::formatEther('100000000000000000'));
    }

    /**
     * 测试任意精度转换
     */
    public function test_parse_units(): void
    {
        // USDT (6 decimals)
        $this->assertEquals('1000000', Units::parseUnits('1', 6));
        $this->assertEquals('1500000', Units::parseUnits('1.5', 6));

        // WBTC (8 decimals)
        $this->assertEquals('100000000', Units::parseUnits('1', 8));
    }

    /**
     * 测试任意精度格式化
     */
    public function test_format_units(): void
    {
        // USDT (6 decimals)
        $this->assertEquals('1', Units::formatUnits('1000000', 6));
        $this->assertEquals('1.5', Units::formatUnits('1500000', 6));

        // WBTC (8 decimals)
        $this->assertEquals('1', Units::formatUnits('100000000', 8));
    }

    /**
     * 测试 Gwei 转换
     */
    public function test_gwei_conversion(): void
    {
        $this->assertEquals('1000000000', Units::parseGwei('1'));
        $this->assertEquals('1', Units::formatGwei('1000000000'));
        $this->assertEquals('30000000000', Units::parseGwei('30'));
    }

    /**
     * 测试小数精度
     */
    public function test_decimal_precision(): void
    {
        // 很小的值
        $this->assertEquals('1', Units::parseEther('0.000000000000000001'));
        $this->assertEquals('0.000000000000000001', Units::formatEther('1'));

        // 18位小数
        $this->assertEquals('123456789012345678', Units::parseEther('0.123456789012345678'));
    }

    /**
     * 测试零值
     */
    public function test_zero_values(): void
    {
        $this->assertEquals('0', Units::parseEther('0'));
        $this->assertEquals('0', Units::formatEther('0'));
        $this->assertEquals('0', Units::parseUnits('0', 6));
    }
}
