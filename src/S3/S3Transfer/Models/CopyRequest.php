<?php

namespace Aws\S3\S3Transfer\Models;

use Aws\S3\S3Transfer\Progress\TransferListener;
use InvalidArgumentException;

class CopyRequest extends TransferRequest
{
    public static array $configKeys = [
        'target_part_size_bytes',
        'track_progress',
        'concurrency',
    ];

    /** @var array */
    private array $source;

    /** @var array */
    private array $copyRequestArgs;

    /**
     * @param array $source
     * @param array $copyRequestArgs
     * @param array $config
     * @param TransferListener[] $listeners
     * @param TransferListener|null $progressTracker
     */
    public function __construct(
        array             $source,
        array             $copyRequestArgs,
        array             $config = [],
        array             $listeners = [],
        ?TransferListener $progressTracker = null
    )
    {
        parent::__construct($listeners, $progressTracker, $config);
        $this->source = $source;
        $this->copyRequestArgs = $copyRequestArgs;
    }

    /**
     * @param array $source
     * @param array $copyRequestArgs
     * @param array $config
     * @param TransferListener[] $listeners
     * @param TransferListener|null $progressTracker
     * @return CopyRequest
     */
    public static function fromLegacyArgs(
        array             $source,
        array             $copyRequestArgs = [],
        array             $config = [],
        array             $listeners = [],
        ?TransferListener $progressTracker = null
    ): CopyRequest
    {
        return new CopyRequest(
            $source,
            $copyRequestArgs,
            $config,
            $listeners,
            $progressTracker
        );
    }

    /**
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getCopyRequestArgs(): array
    {
        return $this->copyRequestArgs;
    }

    /**
     * Helper method for validating the given source.
     *
     * @return void
     */
    public function validateSource(): void
    {
        // Validate source has Bucket and Key
        $hasBucket = !empty($this->source['Bucket']);
        $hasKey = !empty($this->source['Key']);

        if (!$hasBucket && !$hasKey) {
            throw new InvalidArgumentException(
                "The `Bucket` and `Key` parameters must be provided in the source."
            );
        }
        if (!$hasBucket) {
            throw new InvalidArgumentException(
                "The `Bucket` parameter must be provided in the source array."
            );
        }
        if (!$hasKey) {
            throw new InvalidArgumentException(
                "The `Key` parameter must be provided in the source array."
            );
        }

        $hasDestBucket = !empty($this->copyRequestArgs['Bucket']);
        $hasDestKey = !empty($this->copyRequestArgs['Key']);

        if (!$hasDestBucket) {
            throw new InvalidArgumentException(
                "The `Bucket` parameter must be provided in the copy request arguments."
            );
        }
        if (!$hasDestKey) {
            throw new InvalidArgumentException(
                "The `Key` parameter must be provided in the copy request arguments."
            );
        }

        if ($this->source['Bucket'] === $this->copyRequestArgs['Bucket']
            && $this->source['Key'] === $this->copyRequestArgs['Key']
        ) {
            throw new InvalidArgumentException(
                "Source and destination cannot be the same object"
            );
        }
    }

    /**
     * Helper method for validating required parameters.
     *
     * @param string|null $customMessage
     * @return void
     */
    public function validateRequiredParameters(?string $customMessage = null): void
    {
        foreach (['Bucket', 'Key'] as $param) {
            if (empty($this->copyRequestArgs[$param] ?? null)) {
                throw new InvalidArgumentException(
                    $customMessage
                    ?? "The `$param` parameter must be provided as part of the copy request arguments."
                );
            }
        }
    }
}
