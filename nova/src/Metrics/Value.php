<?php

namespace Laravel\Nova\Metrics;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

abstract class Value extends RangedMetric
{
    /**
     * The element's component.
     *
     * @var string
     */
    public $component = 'value-metric';

    /**
     * Return a value result showing the growth of an count aggregate over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string|null  $column
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    public function count($request, $model, $column = null)
    {
        return $this->aggregate($request, $model, 'count', $column);
    }

    /**
     * Return a value result showing the growth of an average aggregate over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $column
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    public function average($request, $model, $column)
    {
        return $this->aggregate($request, $model, 'avg', $column);
    }

    /**
     * Return a value result showing the growth of a sum aggregate over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $column
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    public function sum($request, $model, $column)
    {
        return $this->aggregate($request, $model, 'sum', $column);
    }

    /**
     * Return a value result showing the growth of a maximum aggregate over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $column
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    public function max($request, $model, $column)
    {
        return $this->aggregate($request, $model, 'max', $column);
    }

    /**
     * Return a value result showing the growth of a minimum aggregate over time.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $column
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    public function min($request, $model, $column)
    {
        return $this->aggregate($request, $model, 'min', $column);
    }

    /**
     * Return a value result showing the growth of a model over a given time frame.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Builder|string  $model
     * @param  string  $function
     * @param  string|null  $column
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    protected function aggregate($request, $model, $function, $column = null)
    {
        $query = $model instanceof Builder ? $model : (new $model)->newQuery();

        $column = $column ?? $query->getModel()->getQualifiedKeyName();

        $previousValue = number_format(with(clone $query)->whereBetween(
            $query->getModel()->getCreatedAtColumn(), $this->previousRange($request->range)
        )->{$function}($column), 0);

        return $this->result(
            number_format(with(clone $query)->whereBetween(
                $query->getModel()->getCreatedAtColumn(), $this->currentRange($request->range)
            )->{$function}($column), 0)
        )->previous($previousValue);
    }

    /**
     * Calculate the previous range and calculate any short-cuts.
     *
     * @param  string|int  $range
     * @return array
     */
    protected function previousRange($range)
    {
        if ($range == 'MTD') {
            return [
                now()->modify('first day of previous month')->setTime(0, 0),
                now()->subMonthsNoOverflow(1),
            ];
        }

        if ($range == 'QTD') {
            return $this->previousQuarterRange();
        }

        if ($range == 'YTD') {
            return [
                now()->subYears(1)->firstOfYear(),
                now()->subYearsNoOverflow(1),
            ];
        }

        return [
            now()->subDays($range * 2),
            now()->subDays($range),
        ];
    }

    /**
     * Calculate the previous quarter range.
     *
     * @return array
     */
    protected function previousQuarterRange()
    {
        return [
            Carbon::firstDayOfPreviousQuarter(),
            now()->subMonthsNoOverflow(3),
        ];
    }

    /**
     * Calculate the current range and calculate any short-cuts.
     *
     * @param  string|int  $range
     * @return array
     */
    protected function currentRange($range)
    {
        if ($range == 'MTD') {
            return [
                now()->firstOfMonth(),
                now(),
            ];
        }

        if ($range == 'QTD') {
            return $this->currentQuarterRange();
        }

        if ($range == 'YTD') {
            return [
                now()->firstOfYear(),
                now(),
            ];
        }

        return [
            now()->subDays($range),
            now(),
        ];
    }

    /**
     * Calculate the previous quarter range.
     *
     * @return array
     */
    protected function currentQuarterRange()
    {
        return [
            Carbon::firstDayOfQuarter(),
            now(),
        ];
    }

    /**
     * Create a new value metric result.
     *
     * @param  mixed  $value
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    public function result($value)
    {
        return new ValueResult($value);
    }
}
