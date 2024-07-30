<?php

namespace JeNe\Snappy\Domain\Model;

use JeNe\Snappy\DTO\SnapshotStoreObject;
use TYPO3\CMS\Extbase\Annotation\Validate;

class Snapshot extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    #[Validate([
        'validator' => 'NotEmpty'
    ])]
    public string $title;
    protected ?\DateTime $crdate = null;
    protected ?string $snapshot = null;

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }


    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Snapshot
     */
    public function setTitle(string $title): Snapshot
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCrdate(): ?\DateTime
    {
        return $this->crdate;
    }

    /**
     * @param \DateTime|null $crdate
     * @return Snapshot
     */
    public function setCrdate(?\DateTime $crdate): Snapshot
    {
        $this->crdate = $crdate;
        return $this;
    }

    public function getSnapshot(): mixed
    {
        return unserialize($this->snapshot);
    }

    public function setSnapshot(mixed $snapshot): Snapshot
    {
        if($snapshot instanceof SnapshotStoreObject) {
            $snapshot = serialize($snapshot);
        }
        $this->snapshot = $snapshot;
        return $this;
    }



}