<?php

namespace Aws\Tests\S3\S3Transfer\Models;

use Aws\S3\S3Transfer\Models\CopyRequest;
use Aws\S3\S3Transfer\Progress\TransferListener;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CopyRequestTest extends TestCase
{
    private array $validSource = ['Bucket' => 'src-bucket', 'Key' => 'src-key'];
    private array $validArgs = ['Bucket' => 'dst-bucket', 'Key' => 'dst-key'];

    public function testGetSourceAndDestinationArgsAreExposedByGetters(): void
    {
        $listeners = [$this->createMock(TransferListener::class)];
        $tracker   = $this->createMock(TransferListener::class);
        $config    = ['concurrency' => 2];

        $req = new CopyRequest(
            $this->validSource,
            $this->validArgs,
            $config,
            $listeners,
            $tracker
        );

        $this->assertSame($this->validSource, $req->getSource());
        $this->assertSame($this->validArgs, $req->getCopyRequestArgs());

        $this->assertSame($listeners, $req->getListeners());
        $this->assertSame($tracker, $req->getProgressTracker());

        $returnedConfig = $req->getConfig();
        $this->assertArrayHasKey('concurrency', $returnedConfig);
        $this->assertEquals(2, $returnedConfig['concurrency']);
    }

    public function testValidateSourceSucceeds()
    {
        $req = CopyRequest::fromLegacyArgs(
            $this->validSource,
            $this->validArgs,
            [],
            [],
            null
        );

        $req->validateSource();
        $req->validateRequiredParameters();
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider sourceValidationProvider
     */
    public function testValidateSourceThrows($src, $args, string $expectedMessage)
    {
        $req = CopyRequest::fromLegacyArgs($src, $args);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $req->validateSource();
    }

    public function sourceValidationProvider(): array
    {
        return [
            [
                [],
                [],
                "The `Bucket` and `Key` parameters must be provided in the source."
            ],
            [
                ['Bucket' => 'a'],
                ['Bucket' => 'b', 'Key' => 'c'],
                "The `Key` parameter must be provided in the source array."
            ],
            [
                ['Bucket' => 'a', 'Key' => 'b'],
                ['Key' => 'c'],
                "The `Bucket` parameter must be provided in the copy request arguments."
            ],
            [
                ['Bucket' => 'a', 'Key' => 'b'],
                ['Bucket' => 'c'],
                "The `Key` parameter must be provided in the copy request arguments."
            ],
            [
                ['Bucket' => 'x', 'Key' => 'y'],
                ['Bucket' => 'x', 'Key' => 'y'],
                "Source and destination cannot be the same object"
            ],
        ];
    }


    /**
     * @dataProvider requiredParamsProvider
     */
    public function testValidateRequiredParameters($args, ?string $customMsg, string $expected)
    {
        $req = CopyRequest::fromLegacyArgs(
            $this->validSource,
            $args,
            [], [], null
        );

        if ($customMsg !== null) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($customMsg);
            $req->validateRequiredParameters($customMsg);
        } else {
            // without custom message, should throw default
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($expected);
            $req->validateRequiredParameters();
        }
    }

    public function requiredParamsProvider(): array
    {
        return [
            // missing bucket
            [['Key' => 'foo'], null, "The `Bucket` parameter must be provided as part of the copy request arguments."],
            // missing key
            [['Bucket' => 'bar'], null, "The `Key` parameter must be provided as part of the copy request arguments."],
            // custom message
            [[], 'Custom!', 'Custom!'],
        ];
    }
}
