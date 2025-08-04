<?php

namespace Aws\S3\S3Transfer;

use Aws\S3\S3Transfer\Models\AbstractResumableTransfer;
use Aws\S3\S3Transfer\Progress\TransferProgressSnapshot;
use Psr\Http\Message\StreamInterface;

/**
 * Represents the state of an incomplete multipart upload that can be resumed.
 */
class ResumableMultipartUpload extends AbstractResumableTransfer
{
    /**
     * Get a string representation of this resumable upload object.
     */
    public function __toString(): string
    {
        $snapshot = $this->getProgressSnapshot();
        return sprintf(
            "ResumableMultipartUpload:\n" .
            "  Upload ID:       %s\n" .
            "  Parts Uploaded:  %d\n" .
            "  Object Size:     %d bytes\n" .
            "  Key:             %s\n" .
            "  Transferred:     %d bytes\n" .
            "  Total Size:      %d bytes\n" .
            "  Reason:          %s\n",
            $this->getUploadId(),
            count($this->getParts()),
            $this->getObjectSize(),
            $snapshot?->getIdentifier() ?? 'N/A',
            $snapshot?->getTransferredBytes() ?? 0,
            $snapshot?->getTotalBytes() ?? 0,
            ($snapshot?->getReason()?->getMessage() ?? 'N/A')
        );
    }

    /**
     * Create a new ResumableMultipartUpload instance.
     *
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
        parent::__construct(
            $source,
            $requestArgs,
            $uploadId,
            $parts,
            $objectSize,
            $progressSnapshot
        );
    }
}
