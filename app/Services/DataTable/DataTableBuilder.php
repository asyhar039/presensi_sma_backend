<?php

namespace App\Services\DataTable;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DataTableBuilder
{
    protected Builder $query;

    protected Request $request;

    protected array $eagerLoads = [];

    protected array $searchColumns = [];

    protected string $searchKey = 'search';

    /** @var array<string, string|Closure(Builder, string): void> */
    protected array $allowedSorts = [];

    protected string $sortKey = 'sortBy';

    protected string $directionKey = 'order';

    protected string $defaultSort = 'id';

    protected string $defaultDirection = 'desc';

    protected string $pageKey = 'page';

    protected string $perPageKey = 'per_page';

    protected int $defaultPerPage = 10;

    protected int $maxPerPage = 50;

    /** @var array<string, Closure(Builder, mixed): void> */
    protected array $filterCallbacks = [];

    /** @var array<string, array<int, string>> */
    protected array $filterRules = [];

    public function __construct(Builder|string $query, ?Request $request = null)
    {
        $this->query = is_string($query) ? $query::query() : $query;
        $this->request = $request ?? request();
    }

    public static function make(Builder|string $query, ?Request $request = null): self
    {
        return new static($query, $request);
    }

    public function with(array|string $relations): self
    {
        $this->eagerLoads = array_merge($this->eagerLoads, (array) $relations);

        return $this;
    }

    public function searchable(array $columns, string $key = 'search'): self
    {
        $this->searchColumns = array_values($columns);
        $this->searchKey = $key;

        return $this;
    }

    public function disableSearch(): self
    {
        $this->searchColumns = [];

        return $this;
    }

    /**
     * @param  array<string, string|Closure(Builder, string): void>  $allowedSorts  Sort key exposed to the API mapped to a column or a callback. Relation sorting must use a callback.
     */
    public function sortable(
        array $allowedSorts,
        string $defaultSort = 'id',
        string $defaultDirection = 'desc',
        string $sortKey = 'sortBy',
        string $directionKey = 'order'
    ): self {
        $this->allowedSorts = $allowedSorts;
        $this->defaultSort = $defaultSort;
        $this->defaultDirection = strtolower($defaultDirection) === 'asc' ? 'asc' : 'desc';
        $this->sortKey = $sortKey;
        $this->directionKey = $directionKey;

        return $this;
    }

    public function paginateParams(
        int $defaultPerPage = 10,
        int $maxPerPage = 50,
        string $pageKey = 'page',
        string $perPageKey = 'per_page'
    ): self {
        $this->defaultPerPage = $defaultPerPage;
        $this->maxPerPage = $maxPerPage;
        $this->pageKey = $pageKey;
        $this->perPageKey = $perPageKey;

        return $this;
    }

    /**
     * @param  array<int, string>  $rules
     * @param  Closure(Builder, mixed): void  $callback
     */
    public function addFilter(string $key, array $rules, Closure $callback): self
    {
        $this->filterRules[$key] = $rules;
        $this->filterCallbacks[$key] = $callback;

        return $this;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validated(): array
    {
        $rules = $this->filterRules;

        if ($this->searchColumns !== []) {
            $rules[$this->searchKey] = ['nullable', 'string', 'max:128'];
        }

        if ($this->allowedSorts !== []) {
            $rules[$this->sortKey] = ['nullable', 'string', 'in:'.implode(',', array_keys($this->allowedSorts))];
            $rules[$this->directionKey] = ['nullable', 'string', 'in:asc,desc,ASC,DESC'];
        }

        $rules[$this->pageKey] = ['nullable', 'integer', 'min:1'];
        $rules[$this->perPageKey] = ['nullable', 'integer', 'min:1', 'max:'.$this->maxPerPage];

        $validator = Validator::make($this->request->all(), $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array<string, mixed> $validated */
        $validated = $validator->validated();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applySearch(array $validated): void
    {
        $term = trim((string) ($validated[$this->searchKey] ?? ''));

        if ($this->searchColumns === [] || $term === '') {
            return;
        }

        $this->query->where(function (Builder $query) use ($term): void {
            foreach ($this->searchColumns as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $relationColumn] = explode('.', $column, 2);
                    $query->orWhereHas($relation, function (Builder $relationQuery) use ($relationColumn, $term): void {
                        $relationQuery->where($relationColumn, 'LIKE', "%{$term}%");
                    });
                } else {
                    $query->orWhere($query->qualifyColumn($column), 'LIKE', "%{$term}%");
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applySorting(array $validated): void
    {
        if ($this->allowedSorts === []) {
            return;
        }

        $sort = (string) ($validated[$this->sortKey] ?? $this->defaultSort);
        $direction = strtolower((string) ($validated[$this->directionKey] ?? $this->defaultDirection));
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $column = $this->allowedSorts[$sort] ?? $this->allowedSorts[$this->defaultSort] ?? $this->defaultSort;

        if ($column instanceof Closure) {
            $column($this->query, $direction);

            return;
        }

        $this->query->orderBy($this->query->qualifyColumn($column), $direction);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applyFilters(array $validated): void
    {
        foreach ($this->filterCallbacks as $key => $callback) {
            $value = $validated[$key] ?? null;

            if ($value !== null && $value !== '') {
                $callback($this->query, $value);
            }
        }
    }

    /**
     * @return LengthAwarePaginator<int, mixed>
     *
     * @throws ValidationException
     */
    public function paginate(): LengthAwarePaginator
    {
        $validated = $this->validated();

        if ($this->eagerLoads !== []) {
            $this->query->with($this->eagerLoads);
        }

        $this->applyFilters($validated);
        $this->applySearch($validated);
        $this->applySorting($validated);

        $perPage = min((int) ($validated[$this->perPageKey] ?? $this->defaultPerPage), $this->maxPerPage);
        $page = (int) ($validated[$this->pageKey] ?? 1);

        return $this->query->paginate(perPage: $perPage, pageName: $this->pageKey, page: $page)->withQueryString();
    }
}
