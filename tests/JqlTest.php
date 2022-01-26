<?php

namespace JqlBuilder\Tests;

use InvalidArgumentException;
use JqlBuilder\Jql;
use PHPUnit\Framework\TestCase;

class JqlTest extends TestCase
{
    /** @test */
    public function it_can_generate_query_with_single_condition(): void
    {
        $query = Jql::query()
            ->whereProject('MY PROJECT')
            ->getQuery();

        self::assertSame('project = "MY PROJECT"', $query);
    }

    /** @test */
    public function it_can_generate_query_with_many_conditions(): void
    {
        $query = Jql::query()
            ->whereProject('MY PROJECT')
            ->whereIssueType('support')
            ->whereStatus(['wip', 'created'], 'in')
            ->getQuery();

        self::assertSame('project = "MY PROJECT" and issuetype = "support" and status in ("wip", "created")', $query);
    }

    /** @test */
    public function it_can_generate_query_with_many_conditions_and_order_by(): void
    {
        $query = Jql::query()
            ->whereProject('MY PROJECT')
            ->whereIssueType('support')
            ->whereStatus(['wip', 'created'], 'in')
            ->orderBy('created', 'asc')
            ->getQuery();

        $expected = 'project = "MY PROJECT" and issuetype = "support" and status in ("wip", "created") order by created asc';

        self::assertSame($expected, $query);
    }

    /** @test */
    public function it_can_generate_query_with_custom_filed_conditions(): void
    {
        $query = Jql::query()
            ->where('customfild_111', '=', 'value')
            ->where('customfild_222', '=', 'value')
            ->getQuery();

        self::assertSame('customfild_111 = "value" and customfild_222 = "value"', $query);
    }

    /** @test */
    public function it_can_generate_query_conditions_based_on_your_condition(): void
    {
        $query = Jql::query()
            ->when('MY PROJECT', fn (Jql $builder, $value) => $builder->whereProject($value))
            ->when(fn (Jql $builder) => false, fn (Jql $builder, $value) => $builder->whereIssueType($value))
            ->getQuery();

        self::assertSame('project = "MY PROJECT"', $query);
    }

    /** @test */
    public function it_can_generate_query_using_raw_query(): void
    {
        $query = Jql::query()
            ->rawQuery('project = "MY PROJECT" order by created asc')
            ->getQuery();

        self::assertSame('project = "MY PROJECT" order by created asc', $query);
    }

    /** @test */
    public function it_can_add_macro(): void
    {
        $builder = new Jql();

        $builder::macro('whereCustom', function ($value) {
            /** @var Jql $this */
            return $this->where('custom', '=', $value);
        });

        /** @noinspection PhpUndefinedMethodInspection */
        $query = $builder->whereCustom('1')->getQuery();

        self::assertSame('custom = "1"', $query);
    }

    /** @test */
    public function it_can_throw_exception_when_invalid_boolean_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Illegal boolean [=] value. only [and, or] is acceptable');
        $this->expectExceptionCode(0);

        Jql::query()->where('project', '=', 'MY PROJECT', '=');
    }

    /** @test */
    public function it_can_throw_exception_when_invalid_operator_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Illegal operator [=] value. only [in, not in, was in, was not in] is acceptable when $value type is array');
        $this->expectExceptionCode(0);

        Jql::query()->where('project', '=', ['MY PROJECT']);
    }

    /** @test */
    public function it_can_escape_quotes_in_value(): void
    {
        $query = Jql::query()
            ->where('summary', '=', 'sub-issue for "TES-xxx"')
            ->getQuery();

        self::assertSame('summary = "sub-issue for \"TES-xxx\""', $query);
    }
}
