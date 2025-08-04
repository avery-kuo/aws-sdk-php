<?php

namespace Aws\S3\S3Transfer\Models;

use Aws\S3\S3Transfer\Progress\TransferProgressSnapshot;
use Psr\Http\Message\StreamInterface;

/**
 * Base model for resumable S3 transfers (upload/download).
 */
abstract class AbstractResumableTransfer
{
    /** @var string|StreamInterface */
    protected string|StreamInterface $source;

    /** @var array */
    protected array $requestArgs;

    /** @var string */
    protected string $uploadId;

    /** @var array */
    protected array $parts;

    /** @var int */
    protected int $objectSize;

    /** @var TransferProgressSnapshot|null */
    protected ?TransferProgressSnapshot $progressSnapshot;

    /**
     * @param string|StreamInterface $source
     * @param array $requestArgs
     * @param string $uploadId
     * @param array $parts
     * @param int $objectSize
     * @param TransferProgressSnapshot|null $progressSnapshot
     */
    public function __construct(
        string|StreamInterface $source,
        array $requestArgs,
        string $uploadId,
        array $parts,
        int $objectSize,
        ?TransferProgressSnapshot $progressSnapshot = null
    ) {
        $this->source = $source;
        $this->requestArgs = $requestArgs;
        $this->uploadId = $uploadId;
        $this->parts = $parts;
        $this->objectSize = $objectSize;
        $this->progressSnapshot = $progressSnapshot;
    }

    public function getSource(): string|StreamInterface
    {
        return $this->source;
    }

    public function getRequestArgs(): array
    {
        return $this->requestArgs;
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function getParts(): array
    {
        return $this->parts;
    }

    public function getObjectSize(): int
    {
        return $this->objectSize;
    }

    public function getProgressSnapshot(): ?TransferProgressSnapshot
    {
        return $this->progressSnapshot;
    }

    public function __toString(): string
    {
        $snapshot = $this->progressSnapshot;

        return sprintf(
            "%s:\n" .
            "  Upload ID:       %s\n" .
            "  Parts Uploaded:  %d\n" .
            "  Object Size:     %d bytes\n" .
            "  Key:             %s\n" .
            "  Transferred:     %d bytes\n" .
            "  Total Size:      %d bytes\n" .
            "  Reason:          %s\n",
            static::class,
            $this->uploadId,
            count($this->parts),
            $this->objectSize > 0 ? $this->objectSize : ($snapshot?->getTotalBytes() ?? 0),
            $snapshot?->getIdentifier() ?? 'N/A',
            $snapshot?->getTransferredBytes() ?? 0,
            $snapshot?->getTotalBytes() ?? 0,
            $snapshot?->getReason()?->getMessage() ?? 'N/A'
        );
    }
}
