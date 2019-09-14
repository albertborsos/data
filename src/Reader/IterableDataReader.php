<?php
declare(strict_types=1);

namespace Yiisoft\Data\Reader;

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Data\Reader\Filter\All;
use Yiisoft\Data\Reader\Filter\Any;
use Yiisoft\Data\Reader\Filter\FilterInterface;
use Yiisoft\Data\Reader\Filter\Equals;
use Yiisoft\Data\Reader\Filter\GreaterThan;
use Yiisoft\Data\Reader\Filter\GreaterThanOrEqual;
use Yiisoft\Data\Reader\Filter\LessThan;
use Yiisoft\Data\Reader\Filter\LessThanOrEqual;
use Yiisoft\Data\Reader\Filter\In;
use Yiisoft\Data\Reader\Filter\Like;
use Yiisoft\Data\Reader\Filter\Not;
use Yiisoft\Data\Reader\Filter\Processor\FilterProcessor;
use Yiisoft\Data\Reader\Filter\Processor\PhpVariableFilterProcessor;

class IterableDataReader implements DataReaderInterface, SortableDataInterface, FilterableDataInterface, OffsetableDataInterface, CountableDataInterface
{
    protected $data;
    private $sort;

    /**
     * @var FilterInterface
     */
    private $filter;
    /**
     * @var FilterProcessor
     */
    private $filterProcessor;

    private $limit = self::DEFAULT_LIMIT;
    private $offset = 0;

    public function __construct(iterable $data)
    {
        $this->data = $data;
    }

    public function withSort(?Sort $sort): self
    {
        $new = clone $this;
        $new->sort = $sort;
        return $new;
    }

    public function getSort(): ?Sort
    {
        return $this->sort;
    }

    /**
     * Sorts data items according to the given sort definition.
     * @param iterable $items the items to be sorted
     * @param Sort $sort the sort definition
     * @return array the sorted items
     */
    private function sortItems(iterable $items, Sort $sort): iterable
    {
        $criteria = $sort->getCriteria();
        if ($criteria !== []) {
            $items = $this->iterableToArray($items);
            ArrayHelper::multisort($items, array_keys($criteria), array_values($criteria));
        }

        return $items;
    }

    protected function matchFilter(array $item, array $filter): bool
    {
        $filterProcessor = $this->getFilterProcessor();
        /* @var $filterProcessor PhpVariableFilterProcessor */
        return $filterProcessor->match($item, $filter);
    }

    public function withFilter(?FilterInterface $filter): self
    {
        $new = clone $this;
        $new->filter = $filter;
        return $new;
    }

    public function withLimit(int $limit): self
    {
        $new = clone $this;
        $new->limit = $limit;
        return $new;
    }

    public function read(): iterable
    {
        $filter = null;
        if ($this->filter !== null) {
            $filter = $this->filter->toArray();
        }

        $data = [];
        $skipped = 0;

        $sortedData = $this->sort === null
            ? $this->data
            : $this->sortItems($this->data, $this->sort);

        foreach ($sortedData as $item) {
            // do not return more than limit items
            if (count($data) === $this->limit) {
                break;
            }

            // skip offset items
            if ($skipped < $this->offset) {
                $skipped++;
                continue;
            }

            // filter items
            if ($filter === null || $this->matchFilter($item, $filter)) {
                $data[] = $item;
            }
        }

        return $data;
    }

    public function withOffset(int $offset): self
    {
        $new = clone $this;
        $new->offset = $offset;
        return $new;
    }

    public function count(): int
    {
        return count($this->read());
    }

    private function iterableToArray(iterable $iterable): array
    {
        return $iterable instanceof \Traversable ? iterator_to_array($iterable, true) : (array)$iterable;
    }

    public function getFilterProcessor(): FilterProcessor
    {
        if(!isset($this->filterProcessor)) {
            $this->filterProcessor = new PhpVariableFilterProcessor();
        }
        return $this->filterProcessor;
    }
}
