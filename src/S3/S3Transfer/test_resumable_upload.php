<?php
require __DIR__ . '/../../../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\S3Transfer\S3TransferManager;
use Aws\S3\S3Transfer\Models\ResumableMultipartUpload;
use Aws\S3\S3Transfer\Progress\TransferProgressSnapshot;
use Psr\Http\Message\StreamInterface;

// 1) A “broken” stream that throws on read after a few bytes:
class BrokenStream implements StreamInterface
{
    private int $bytesRead = 0;
    public function __toString(): string { return ''; }
    public function close(): void {}
    public function detach() { return null; }
    public function getSize(): ?int { return 10 * 1024 * 1024; }      // 10 MB
    public function tell(): int { return $this->bytesRead; }
    public function eof(): bool { return $this->bytesRead >= $this->getSize(); }
    public function isSeekable(): bool { return false; }
    public function seek($offset, $whence = SEEK_SET): void {}
    public function rewind(): void {}
    public function isWritable(): bool { return false; }
    public function write($string): int { throw new \RuntimeException("Not writable"); }
    public function isReadable(): bool { return true; }
    public function read($length): string {
        $this->bytesRead += $length;
        if ($this->bytesRead > 2 * 1024 * 1024) { // fail after ~2 MB
            throw new \RuntimeException("Simulated network failure");
        }
        return str_repeat("\0", $length);
    }
    public function getContents(): string { return ''; }
    public function getMetadata($key = null): mixed { return null; }
}

// 2) Configure a real S3 client (you may point at a local emulator or real bucket)
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'us-east-2',
    // 'endpoint' => 'http://localhost:4566', // if you’re using localstack
]);

$manager = new S3TransferManager($s3Client);

echo "Starting resumable multipart upload test…\n";

// 3) Kick off the upload with resumable enabled:
try {
    $promise = $manager->upload(
        new BrokenStream(),
        [
            'Bucket' => 'resumable-upload-test',
            'Key'    => 'test.txt',
        ],
        [
            'resumable_upload_object'          => true,
            'multipart_upload_threshold_bytes' => 5 * 1024 * 1024, // 5 MB
            'part_size'                        => 5 * 1024 * 1024, // 2 MB
        ]
    );

    $result = $promise->wait();

    if ($result instanceof ResumableMultipartUpload) {
        echo "✅ Received ResumableMultipartUpload:\n";
        echo "   Upload ID:      " . $result->getUploadId() . "\n";
        echo "   Parts Uploaded: " . count($result->getParts()) . "\n";
        echo "   Object Size:    " . $result->getObjectSize() . " bytes\n";
        echo "   Transferred:    " . ($result->getProgressSnapshot()?->getTransferredBytes() ?? 0) . " bytes\n";
    } else {
        echo "❌ Unexpected result type: " . get_class($result) . "\n";
        var_dump($result);
    }
} catch (\Throwable $e) {
    echo "❌ Test threw an exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
