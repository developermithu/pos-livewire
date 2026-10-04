<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use InvalidArgumentException;

/**
 * Binds an integer `*_cents` column to a {@see Money} object.
 *
 * The currency lives in a sibling column — one per row, not one per amount —
 * so a row's cost and price cannot drift into different currencies. Name that
 * column with a cast argument when it is not `currency`:
 * `'price_cents' => Money::class.':settlement_currency'`.
 *
 * @implements CastsAttributes<Money, Money|int|numeric-string|null>
 */
final readonly class MoneyCast implements CastsAttributes
{
    public function __construct(private string $currencyColumn = 'currency') {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(EloquentModel $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return new Money((int) $value, $this->currencyFrom($attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function set(EloquentModel $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if (is_int($value)) {
            return [$key => $value];
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException(
                sprintf('[%s] must be set to a %s instance or an integer of minor units.', $key, Money::class)
            );
        }

        return [
            $key => $value->minorUnits,
            $this->currencyColumn => $value->currency,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function currencyFrom(array $attributes): string
    {
        $currency = $attributes[$this->currencyColumn] ?? null;

        return is_string($currency) && $currency !== ''
            ? $currency
            : Money::DEFAULT_CURRENCY;
    }
}
