<?php

namespace JeNe\Snappy\Event;

use JeNe\Snappy\DTO\SnapshotStoreObject;


/**
 * Base Event for all Snapshot Events
 */
class AbstractSnapshotEvent
{

    final public function __construct(
        private readonly SnapshotStoreObject $snapshotStoreObject
    )
    {}

    /**
     * @return SnapshotStoreObject
     */
    final public function getSnapshotStoreObject(): SnapshotStoreObject
    {
        return $this->snapshotStoreObject;
    }
}