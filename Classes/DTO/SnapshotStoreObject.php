<?php

namespace JeNe\Snappy\DTO;

class SnapshotStoreObject implements \JsonSerializable, \Stringable
{

    public function __construct(
        public readonly array $pageData = [],
        protected  array $elements = []
    )
    {}


    public function getElements():array
    {
        return $this->elements;
    }
    public function getElementDataKeys():?array
    {
        return array_keys($this->elements);
    }
    public function getElementData(string $key):?array
    {
        return $this->elements[$key] ?? null;
    }
    public function addElementData(string $key, array $data):self
    {
        $this->elements[$key] = $data;
        return $this;
    }
    public function __serialize(): array
    {
        return $this->__toArray();
    }

    public function __unserialize(array $data): void
    {
        $this->pageData = $data['pageData'];
        $this->elements = $data['elements'];

    }

    public function __toArray():array
    {
        return [
            'pageData' => $this->pageData,
            'elements' => $this->elements
        ];
    }

    public function __toString(): string
    {
        return json_encode($this);
    }


    public function jsonSerialize(): mixed
    {
        return $this->__toArray();
    }


}