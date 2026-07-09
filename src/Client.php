<?php

namespace Kipchak\Driver\S3;

use AsyncAws\SimpleS3\SimpleS3Client;

/**
 * SimpleS3Client with a connection-level default multipart PartSize.
 *
 * AsyncAws only accepts PartSize per upload() call; this subclass lets the
 * driver config set a default for the connection, which individual calls can
 * still override by passing their own PartSize.
 */
class Client extends SimpleS3Client
{
    /**
     * Megabytes per multipart part. Must be a power of 2, max 4096.
     * AsyncAws' default of 64 supports objects up to 64MB x 10,000 parts = 640GB.
     */
    private int $defaultPartSize = 64;

    public function setDefaultPartSize(int $megabytes): void
    {
        if ($megabytes < 1 || $megabytes > 4096 || ($megabytes & ($megabytes - 1)) !== 0) {
            throw new \InvalidArgumentException(
                'part_size must be a power of 2 between 1 and 4096 (megabytes), got ' . $megabytes
            );
        }
        $this->defaultPartSize = $megabytes;
    }

    public function getDefaultPartSize(): int
    {
        return $this->defaultPartSize;
    }

    public function upload(string $bucket, string $key, $object, array $options = []): void
    {
        $options['PartSize'] = $options['PartSize'] ?? $this->defaultPartSize;

        parent::upload($bucket, $key, $object, $options);
    }
}
