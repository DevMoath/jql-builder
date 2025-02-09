<?php

use JqlBuilder\Jql;

it('can generate query', function () {
    $builder = new Jql;

    $query = $builder->where('project', '=', 'MY PROJECT')->getQuery();

    $expected = 'project = "MY PROJECT"';

    expect($query)->toBe($expected);

    $builder = new Jql;

    $query = $builder
        ->where('project', '=', 'MY PROJECT')
        ->where('status', 'in', ['New', 'Done'])
        ->orWhere('status', ['New', 'Done'])
        ->orWhere('summary', '~', 'sub-issue for "TES-xxx"')
        ->orWhere('labels', '=', 'support')
        ->when(false, fn (Jql $builder, mixed $value) => $builder->where('creator', '=', 'admin'))
        ->when(true, fn (Jql $builder, mixed $value) => $builder->where('creator', '=', 'guest'))
        ->orderBy('created', 'asc')
        ->getQuery();

    $expected = 'project = "MY PROJECT" and status in ("New", "Done") or status in ("New", "Done") or summary ~ "sub-issue for \"TES-xxx\"" or labels = "support" and creator = "guest" order by created asc';

    expect($query)->toBe($expected);

    $builder = new Jql;

    $query = (string) $builder
        ->where('project', 'MY PROJECT')
        ->where('status', ['New', 'Done'])
        ->orWhere('status', ['New', 'Done'])
        ->orWhere('summary', '~', 'sub-issue for "TES-xxx"')
        ->orWhere('labels', 'support')
        ->when(false, fn (Jql $builder, mixed $value) => $builder->where('creator', 'admin'))
        ->when(true, fn (Jql $builder, mixed $value) => $builder->where('creator', 'guest'))
        ->whenNot(false, fn (Jql $builder, mixed $value) => $builder->orWhere('creator', 'admin'))
        ->whenNot(true, fn (Jql $builder, mixed $value) => $builder->orWhere('creator', 'guest'))
        ->orderBy('created', 'asc');

    $expected = 'project = "MY PROJECT" and status in ("New", "Done") or status in ("New", "Done") or summary ~ "sub-issue for \"TES-xxx\"" or labels = "support" and creator = "guest" or creator = "admin" order by created asc';

    expect($query)->toBe($expected);
});

it('can generate raw query', function () {
    $query = (string) (new Jql)->rawQuery('project = "MY PROJECT"');

    $expected = 'project = "MY PROJECT"';

    expect($query)->toBe($expected);
});

it('can generate query with grouped conditions', function () {
    $actualQueries = [
        (new Jql)->where('creator', '=', '1646667083862@mailinator.com')
            ->where(function (Jql $builder) {
                $builder->where('project', '=', 'A')
                    ->where('status', '=', '"Closed"');
            })
            ->orWhere(function (Jql $builder) {
                $builder->where('project', '=', '"B"')
                    ->where('status', '!=', 'Closed');
            })->getQuery(),
        (new Jql)->where(function (Jql $builder) {
            $builder->where('project', '=', 'A')
                ->where('status', '=', 'Closed');
        })->orWhere(function (Jql $builder) {
            $builder->where('project', '=', 'B')
                ->where('status', '!=', 'Closed');
        })->getQuery(),
    ];

    $expectedQueries = [
        'creator = "1646667083862@mailinator.com" and (project = "A" and status = "\"Closed\"") or (project = "\"B\"" and status != "Closed")',
        '(project = "A" and status = "Closed") or (project = "B" and status != "Closed")',
    ];

    expect($actualQueries)->toBe($expectedQueries);
});

it('will quote custom field that contains spaces', function () {
    $query = (new Jql)->where('project name', '=', 'MY PROJECT')->getQuery();

    $expected = '"project name" = "MY PROJECT"';

    expect($query)->toBe($expected);
});

it('can add macro', function () {
    $builder = new Jql;

    $builder::macro('whereCustom', function (mixed $value) {
        /** @var Jql $this */
        return $this->where('custom', '=', $value);
    });

    /** @noinspection PhpUndefinedMethodInspection */
    $query = $builder->whereCustom('1')->getQuery();

    $expected = 'custom = "1"';

    expect($query)->toBe($expected);
});

it('will throw exception when invalid boolean passed', function () {
    (new Jql)->where('project', '=', 'MY PROJECT', '=');
})->throws(InvalidArgumentException::class, 'Illegal boolean [=] value. only [and, or] is acceptable');

it('will throw exception when invalid operator passed', function () {
    (new Jql)->where('project', '=', ['MY PROJECT']);
})->throws(InvalidArgumentException::class, 'Illegal operator [=] value. only [in, not in, was in, was not in] is acceptable when $value type is array');

it('can reset the current query', function () {
    $query = new Jql;

    $query->where('project', 'name');

    expect($query)->getQuery()->toBe('project = "name"');

    $query->reset();

    expect($query)->getQuery()->toBeEmpty();
});
