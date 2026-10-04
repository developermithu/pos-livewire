<?php

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Support\Number;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An amount of money held as an integer number of minor units.
 *
 * Money is never a float in this application. Binary floating point cannot
 * represent most decimal cash amounts exactly, so totalling a basket of them
 * drifts — the classic 0.1 + 0.2 problem, applied to a customer's receipt.
 * Every monetary column is therefore an integer `*_cents` column plus a
 * currency code, and this object is what the application passes around.
 *
 * @see MoneyCast for the Eloquent binding.
 */
final readonly class Money implements Castable, JsonSerializable, Stringable
{
    public const string DEFAULT_CURRENCY = 'USD';

    /**
     * The number of minor units in one major unit.
     *
     * Every currency this application handles today is two-decimal. Zero- and
     * three-decimal currencies (JPY, KWD) would need a per-currency exponent;
     * that is a deliberate omission, not an oversight.
     */
    public const int SCALE = 100;

    /**
     * @param  int  $minorUnits  Whole minor units — cents, pence, paisa.
     * @param  string  $currency  ISO 4217 alpha-3 code.
     */
    public function __construct(
        public int $minorUnits,
        public string $currency = self::DEFAULT_CURRENCY,
    ) {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException("Currency must be an ISO 4217 alpha-3 code, got [{$currency}].");
        }
    }

    public static function fromMinorUnits(int $minorUnits, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self($minorUnits, $currency);
    }

    /**
     * Build from a major-unit amount such as `12.34` or `'12.34'`.
     *
     * Strings are preferred: a float argument has already lost precision
     * before this method sees it, so it is rounded on the way in.
     */
    public static function fromDecimal(string|float|int $amount, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self((int) round(((float) $amount) * self::SCALE), $currency);
    }

    public static function zero(string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self(0, $currency);
    }

    /**
     * The amount in major units, for display and for form inputs.
     */
    public function toDecimal(): float
    {
        return $this->minorUnits / self::SCALE;
    }

    /**
     * A localised, symbol-prefixed rendering: `$12.34`.
     */
    public function format(?string $locale = null): string
    {
        $formatted = Number::currency($this->toDecimal(), $this->currency, $locale);

        // Number::currency returns false when intl cannot format the currency.
        // A receipt showing nothing is worse than one showing "USD 19.99".
        return $formatted !== false
            ? $formatted
            : sprintf('%s %s', $this->currency, number_format($this->toDecimal(), 2));
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits + $other->minorUnits, $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->minorUnits - $other->minorUnits, $this->currency);
    }

    /**
     * Scale the amount, rounding half up to the nearest minor unit.
     */
    public function multipliedBy(int|float $multiplier): self
    {
        return new self((int) round($this->minorUnits * $multiplier), $this->currency);
    }

    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    public function equals(self $other): bool
    {
        return $this->minorUnits === $other->minorUnits
            && $this->currency === $other->currency;
    }

    /**
     * The margin this price earns over the given cost, as a percentage of the
     * price. Returns null when the price is zero and the figure is undefined.
     */
    public function marginOver(self $cost): ?float
    {
        $this->assertSameCurrency($cost);

        if ($this->minorUnits === 0) {
            return null;
        }

        return (($this->minorUnits - $cost->minorUnits) / $this->minorUnits) * 100;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot combine {$this->currency} with {$other->currency}; convert first."
            );
        }
    }

    /**
     * @param  array<int, string>  $arguments
     */
    public static function castUsing(array $arguments): MoneyCast
    {
        return new MoneyCast($arguments[0] ?? 'currency');
    }

    /**
     * @return array{minor_units: int, currency: string, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'minor_units' => $this->minorUnits,
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
