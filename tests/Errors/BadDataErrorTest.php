<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\BadDataError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BadDataErrorTest extends TestCase
{
    /**
     */
    public function test_it_should_have_correct_error_code(): void
    {
        $error = new BadDataError('test');

        $this->assertSame('BAD_DATA', $error->code);
        $this->assertSame('BAD_DATA', BadDataError::CODE);
    }

    /**
     */
    public function test_it_should_store_data_and_expected(): void
    {
        $error = new BadDataError('test', '0xinvalid', 'hex string');

        $this->assertSame('0xinvalid', $error->data);
        $this->assertSame('hex string', $error->expected);
    }

    /**
     */
    public function test_it_should_create_decode_error(): void
    {
        $error = BadDataError::decodeError('0x1234', 'invalid function signature');

        $this->assertSame('数据解码失败: invalid function signature', $error->getMessage());
        $this->assertSame('0x1234', $error->data);
    }

    /**
     */
    public function test_it_should_create_decode_error_without_reason(): void
    {
        $error = BadDataError::decodeError('0x1234');

        $this->assertSame('数据解码失败', $error->getMessage());
    }

    /**
     */
    public function test_it_should_create_encode_error(): void
    {
        $error = BadDataError::encodeError(['complex' => 'data'], 'unsupported type');

        $this->assertSame('数据编码失败: unsupported type', $error->getMessage());
        $this->assertSame(['complex' => 'data'], $error->data);
    }

    /**
     */
    public function test_it_should_include_data_in_info(): void
    {
        $error = new BadDataError('test', 'data', 'expected');
        $array = $error->toArray();

        $this->assertSame('data', $array['info']['data']);
        $this->assertSame('expected', $array['info']['expected']);
    }
}
