<?php

declare(strict_types=1);

namespace Tests\Ethers\Errors;

use Ethers\Errors\ReplacementUnderpricedError;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ReplacementUnderpricedErrorTest extends TestCase
{
    /**
     */
    public function test_it_should_have_correct_error_code(): void
    {
        $error = new ReplacementUnderpricedError();

        $this->assertSame('REPLACEMENT_UNDERPRICED', $error->code);
        $this->assertSame('REPLACEMENT_UNDERPRICED', ReplacementUnderpricedError::CODE);
    }

    /**
     */
    public function test_it_should_store_gas_prices(): void
    {
        $error = new ReplacementUnderpricedError(
            'replacement transaction underpriced',
            '20000000000',
            '22000000000'
        );

        $this->assertSame('20000000000', $error->currentGasPrice);
        $this->assertSame('22000000000', $error->requiredGasPrice);
    }

    /**
     */
    public function test_it_should_create_from_rpc_error(): void
    {
        $error = ReplacementUnderpricedError::fromRpcError(
            -32000,
            'replacement transaction underpriced',
            ['nonce' => 10, 'gasPrice' => '20000000000']
        );

        $this->assertSame('replacement transaction underpriced', $error->getMessage());
        $this->assertSame('REPLACEMENT_UNDERPRICED', $error->code);
        $this->assertArrayHasKey('rpcCode', $error->info);
        $this->assertSame(-32000, $error->info['rpcCode']);
    }

    /**
     */
    public function test_it_should_convert_to_array(): void
    {
        $error = new ReplacementUnderpricedError(
            'replacement transaction underpriced',
            '20000000000',
            '22000000000'
        );

        $array = $error->toArray();

        $this->assertSame('REPLACEMENT_UNDERPRICED', $array['code']);
        $this->assertSame('replacement transaction underpriced', $array['message']);
        $this->assertArrayHasKey('info', $array);
    }
}
