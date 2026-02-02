<?php

declare(strict_types=1);

namespace Tests\Ethers\Contract;

use Ethers\Contract\Interface_;
use PHPUnit\Framework\TestCase;

/**
 * Interface_ 测试
 */
class InterfaceTest extends TestCase
{
    /**
     * 测试 JSON ABI 解析
     */
    public function test_json_abi_parsing(): void
    {
        $abi = [
            [
                'type' => 'function',
                'name' => 'balanceOf',
                'inputs' => [['name' => 'account', 'type' => 'address']],
                'outputs' => [['name' => '', 'type' => 'uint256']],
                'stateMutability' => 'view',
            ],
            [
                'type' => 'function',
                'name' => 'transfer',
                'inputs' => [
                    ['name' => 'to', 'type' => 'address'],
                    ['name' => 'amount', 'type' => 'uint256'],
                ],
                'outputs' => [['name' => '', 'type' => 'bool']],
                'stateMutability' => 'nonpayable',
            ],
            [
                'type' => 'event',
                'name' => 'Transfer',
                'inputs' => [
                    ['name' => 'from', 'type' => 'address', 'indexed' => true],
                    ['name' => 'to', 'type' => 'address', 'indexed' => true],
                    ['name' => 'value', 'type' => 'uint256', 'indexed' => false],
                ],
            ],
        ];

        $interface = new Interface_($abi);

        // 获取函数
        $balanceOf = $interface->getFunction('balanceOf');
        $this->assertNotNull($balanceOf);
        $this->assertEquals('balanceOf(address)', $balanceOf['signature']);

        // 获取事件
        $transfer = $interface->getEvent('Transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals('Transfer(address,address,uint256)', $transfer['signature']);
    }

    /**
     * 测试人类可读 ABI 解析
     */
    public function test_human_readable_abi(): void
    {
        $fragments = [
            'function name() view returns (string)',
            'function symbol() view returns (string)',
            'function balanceOf(address owner) view returns (uint256)',
            'function transfer(address to, uint256 amount) returns (bool)',
            'function approve(address spender, uint256 amount) returns (bool)',
            'event Transfer(address indexed from, address indexed to, uint256 value)',
            'event Approval(address indexed owner, address indexed spender, uint256 value)',
            'constructor(string name, string symbol)',
        ];

        $interface = new Interface_($fragments);

        // 测试函数解析
        $name = $interface->getFunction('name');
        $this->assertNotNull($name);
        $this->assertEquals('name', $name['name']);
        $this->assertEquals('view', $name['stateMutability']);
        $this->assertCount(0, $name['inputs']);
        $this->assertCount(1, $name['outputs']);
        $this->assertEquals('string', $name['outputs'][0]['type']);

        $balanceOf = $interface->getFunction('balanceOf');
        $this->assertNotNull($balanceOf);
        $this->assertEquals('balanceOf', $balanceOf['name']);
        $this->assertCount(1, $balanceOf['inputs']);
        $this->assertEquals('address', $balanceOf['inputs'][0]['type']);

        $transfer = $interface->getFunction('transfer');
        $this->assertNotNull($transfer);
        $this->assertEquals('nonpayable', $transfer['stateMutability']);
        $this->assertCount(2, $transfer['inputs']);

        // 测试事件解析
        $transferEvent = $interface->getEvent('Transfer');
        $this->assertNotNull($transferEvent);
        $this->assertEquals('Transfer', $transferEvent['name']);
        $this->assertCount(3, $transferEvent['inputs']);
        $this->assertTrue($transferEvent['inputs'][0]['indexed']);
        $this->assertTrue($transferEvent['inputs'][1]['indexed']);
        $this->assertFalse($transferEvent['inputs'][2]['indexed']);

        // 测试构造函数解析
        $constructor = $interface->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(2, $constructor['inputs']);
        $this->assertEquals('string', $constructor['inputs'][0]['type']);
    }

    /**
     * 测试函数编码
     */
    public function test_encode_function_data(): void
    {
        $interface = new Interface_([
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $data = $interface->encodeFunctionData('transfer', [
            '0x1234567890123456789012345678901234567890',
            '1000000000000000000',
        ]);

        $this->assertStringStartsWith('0xa9059cbb', $data);
    }

    /**
     * 测试函数解码
     */
    public function test_decode_function_data(): void
    {
        $interface = new Interface_([
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        // 构造编码数据
        $data = $interface->encodeFunctionData('transfer', [
            '0x1234567890123456789012345678901234567890',
            '1000000000000000000',
        ]);

        // 解码
        $decoded = $interface->decodeFunctionData('transfer', $data);
        $this->assertEquals('0x1234567890123456789012345678901234567890', $decoded[0]);
        $this->assertEquals('1000000000000000000', $decoded[1]);
    }

    /**
     * 测试构造函数编码
     */
    public function test_encode_deploy(): void
    {
        $interface = new Interface_([
            'constructor(string name, uint256 amount)',
        ]);

        $encoded = $interface->encodeDeploy(['TestToken', '1000000000000000000']);
        $this->assertStringStartsWith('0x', $encoded);
    }

    /**
     * 测试通过选择器获取函数
     */
    public function test_get_function_by_selector(): void
    {
        $interface = new Interface_([
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $func = $interface->getFunction('0xa9059cbb');
        $this->assertNotNull($func);
        $this->assertEquals('transfer', $func['name']);
    }

    /**
     * 测试格式化输出
     */
    public function test_format(): void
    {
        $abi = [
            [
                'type' => 'function',
                'name' => 'transfer',
                'inputs' => [
                    ['name' => 'to', 'type' => 'address'],
                    ['name' => 'amount', 'type' => 'uint256'],
                ],
                'outputs' => [['name' => '', 'type' => 'bool']],
                'stateMutability' => 'nonpayable',
            ],
        ];

        $interface = new Interface_($abi);
        $formatted = $interface->format('minimal');

        $this->assertIsArray($formatted);
        $this->assertContains('function transfer(address to, uint256 amount) returns (bool)', $formatted);
    }

    /**
     * 测试复杂人类可读 ABI
     */
    public function test_complex_human_readable_abi(): void
    {
        $fragments = [
            'function swap(uint256 amount0Out, uint256 amount1Out, address to, bytes calldata data)',
            'function getReserves() view returns (uint112 reserve0, uint112 reserve1, uint32 blockTimestampLast)',
            'event Sync(uint112 reserve0, uint112 reserve1)',
            'error InsufficientLiquidity()',
        ];

        $interface = new Interface_($fragments);

        // 测试 swap 函数
        $swap = $interface->getFunction('swap');
        $this->assertNotNull($swap);
        $this->assertCount(4, $swap['inputs']);
        $this->assertEquals('bytes', $swap['inputs'][3]['type']);

        // 测试 getReserves 返回多值
        $getReserves = $interface->getFunction('getReserves');
        $this->assertNotNull($getReserves);
        $this->assertCount(3, $getReserves['outputs']);
        $this->assertEquals('uint112', $getReserves['outputs'][0]['type']);

        // 测试错误
        $error = $interface->getError('InsufficientLiquidity');
        $this->assertNotNull($error);
    }

    /**
     * 测试 payable 函数
     */
    public function test_payable_function(): void
    {
        $interface = new Interface_([
            'function deposit() payable',
            'function withdraw(uint256 amount)',
        ]);

        $deposit = $interface->getFunction('deposit');
        $this->assertEquals('payable', $deposit['stateMutability']);

        $withdraw = $interface->getFunction('withdraw');
        $this->assertEquals('nonpayable', $withdraw['stateMutability']);
    }

    /**
     * 测试静态方法 from
     */
    public function test_static_from(): void
    {
        $interface = Interface_::from([
            'function transfer(address to, uint256 amount) returns (bool)',
        ]);

        $this->assertInstanceOf(Interface_::class, $interface);
        $this->assertNotNull($interface->getFunction('transfer'));
    }
}
