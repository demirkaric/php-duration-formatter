<?php

declare(strict_types=1);

namespace Demirk\PhpDurationFormatter;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

final class TimeDuration implements JsonSerializable, Stringable
{
    private const SECOND = 1;
    private const MINUTE = 60;
    private const HOUR = 3600;
    private const DAY = 86400;
    private int $days = 0;
    private int $hours = 0;
    private int $minutes = 0;
    private float|int $seconds = 0.0;

    public function __construct(
        float|int|string|null  $duration = null,
        public readonly int    $hoursPerDay = 24,
        public readonly string $format = 'hh:mm:SS',
    )
    {
        if (null !== $duration) {
            $this->parse($duration);
        }
    }

    /**
     * Create a TimeDuration instance from seconds
     */
    public static function fromSeconds(float|int $seconds, int $hoursPerDay = 24, string $format = 'hh:mm:SS'): self
    {
        return new self($seconds, $hoursPerDay, $format);
    }

    /**
     * Create a TimeDuration instance from minutes
     */
    public static function fromMinutes(float|int $minutes, int $hoursPerDay = 24, string $format = 'hh:mm:SS'): self
    {
        return new self($minutes * self::MINUTE, $hoursPerDay, $format);
    }

    /**
     * Create a TimeDuration instance from hours
     */
    public static function fromHours(float|int $hours, int $hoursPerDay = 24, string $format = 'hh:mm:SS'): self
    {
        return new self($hours * self::HOUR, $hoursPerDay, $format);
    }

    /**
     * Create a TimeDuration instance from days
     */
    public static function fromDays(float|int $days, int $hoursPerDay = 24, string $format = 'hh:mm:SS'): self
    {
        return new self($days * self::DAY, $hoursPerDay, $format);
    }

    /**
     * Create a TimeDuration instance from a string format
     */
    public static function fromString(string $duration, int $hoursPerDay = 24, string $format = 'hh:mm:SS'): self
    {
        return new self($duration, $hoursPerDay, $format);
    }

    /**
     * Create an empty TimeDuration instance (0 duration)
     */
    public static function zero(int $hoursPerDay = 24, string $format = 'hh:mm:SS'): self
    {
        return new self(0, $hoursPerDay, $format);
    }

    /**
     * Parses a duration value given in various formats and converts it into structured time components.
     *
     * Supported input formats:
     * - Numeric (int/float): treated as seconds (e.g. `3600` = 1 hour)
     * - String formats:
     *   - `"1h 30m"`, `"2h 15m 20s"`, `"1d 2h"` (unit-based)
     *   - `"01:30"` or `"01:30:45"` (colon-separated format)
     *   - `"1d"` (days only)
     *
     * Internally, the method:
     * - Resets the current state
     * - Extracts duration from recognized patterns
     * - Aggregates all values into total seconds
     * - Delegates parsing to `parseNumeric()` to normalize the components
     *
     * If the total parsed duration is 0 or invalid, `false` is returned.
     *
     * @param float|int|string $duration Duration input in numeric or string format
     *
     * @return false|TimeDuration Returns the instance on success or false on failure
     */
    public function parse(float|int|string $duration): bool|TimeDuration
    {
        $this->reset();

        if (is_numeric($duration)) {
            if ($duration < 0) {
                return false; // Negative durations are not allowed
            }

            return $this->parseNumeric((float)$duration);
        }

        if (is_string($duration) && $this->parseIso8601($duration)) {
            return $this;
        }

        $totalSeconds = 0.0;
        $hasMatch = false;

        if (preg_match('/([0-9.]+)\s?[dD]/', $duration, $matches)) {
            $num = (float)$matches[1];
            $totalSeconds += $num * self::DAY;
            $hasMatch = true;
        }

        $regex = '/\b(?P<hours>\d{1,4}):(?P<minutes>\d{1,4})(?::(?P<seconds>\d{1,4}))?\b/';

        if (preg_match($regex, $duration, $matches)) {
            $totalSeconds += (isset($matches['hours']) ? (int)$matches['hours'] : 0) * self::HOUR;
            $totalSeconds += (isset($matches['minutes']) ? (int)$matches['minutes'] : 0) * self::MINUTE;
            $totalSeconds += isset($matches['seconds']) ? (int)$matches['seconds'] : 0;
            $hasMatch = true;
        } else {
            $times = [
                self::HOUR => '/([0-9.]+)\s?[hH]/',
                self::MINUTE => '/([0-9]{1,4})\s?[mM]/',
                self::SECOND => '/([0-9]{1,4}(\.\d+)?)\s?[sS]/',
            ];

            foreach ($times as $unit => $regex) {
                if (preg_match($regex, $duration, $matches)) {
                    $num = (float)$matches[1];
                    $totalSeconds += $num * $unit;
                    $hasMatch = true;
                }
            }
        }

        if (!$hasMatch || $totalSeconds < 0) {
            return false;
        }

        $this->parseNumeric($totalSeconds);

        return $this;
    }

    private function parseIso8601(string $duration): bool
    {
        $isoRegex = '/^P(?:(?P<weeks>\d+(?:\.\d+)?)W|(?:(?P<days>\d+(?:\.\d+)?)D)?(?:T(?:(?P<hours>\d+(?:\.\d+)?)H)?(?:(?P<minutes>\d+(?:\.\d+)?)M)?(?:(?P<seconds>\d+(?:\.\d+)?)S)?)?)$/i';

        if (!preg_match($isoRegex, $duration, $matches)) {
            return false;
        }

        $hasValue = false;
        $totalSeconds = 0.0;

        if (isset($matches['weeks']) && $matches['weeks'] !== '') {
            $hasValue = true;
            $totalSeconds += (float)$matches['weeks'] * 7 * self::DAY;

            if (!empty($matches['days']) || !empty($matches['hours']) || !empty($matches['minutes']) || !empty($matches['seconds'])) {
                return false; // Weeks cannot be combined with other designators
            }
        }

        if (isset($matches['days']) && $matches['days'] !== '') {
            $hasValue = true;
            $totalSeconds += (float)$matches['days'] * self::DAY;
        }

        if (isset($matches['hours']) && $matches['hours'] !== '') {
            $hasValue = true;
            $totalSeconds += (float)$matches['hours'] * self::HOUR;
        }

        if (isset($matches['minutes']) && $matches['minutes'] !== '') {
            $hasValue = true;
            $totalSeconds += (float)$matches['minutes'] * self::MINUTE;
        }

        if (isset($matches['seconds']) && $matches['seconds'] !== '') {
            $hasValue = true;
            $totalSeconds += (float)$matches['seconds'];
        }

        if (!$hasValue) {
            return false;
        }

        $this->parseNumeric($totalSeconds);

        return true;
    }

    /**
     * Resets all internal time components to their default zero values.
     * Useful when reusing the instance or before parsing a new duration.
     */
    private function reset(): void
    {
        $this->seconds = 0.0;
        $this->minutes = 0;
        $this->hours = 0;
        $this->days = 0;
    }

    /**
     * Parses a numeric duration (in seconds) and decomposes it into days, hours, minutes, and seconds.
     *
     * The method performs the following steps:
     * - Converts the total seconds into minutes, hours, and days based on thresholds.
     * - Maintains decimal precision for seconds if the input is a float.
     * - Ensures seconds are set to 0.0 if the defined format string does not include a seconds token.
     *
     * Example:
     *     90061 → 1 day, 1 hour, 1 minute, 1 second
     *
     * @param float|int $duration Duration in seconds (can be float for sub-second precision).
     *
     * @return TimeDuration Returns the current instance with updated time components.
     */
    private function parseNumeric(float|int $duration): TimeDuration
    {
        // Allow zero duration
        if ($duration < 0) {
            return $this;
        }

        $this->seconds = (float)$duration;

        if ($this->seconds >= 60) {
            $this->minutes = (int)floor($this->seconds / 60);

            // count current precision
            $precision = 0;
            if (($delimiterPos = strpos((string)$this->seconds, '.')) !== false) {
                $precision = strlen(substr((string)$this->seconds, $delimiterPos + 1));
            }

            $this->seconds = round(($this->seconds - ($this->minutes * 60)), $precision);
        }

        if ($this->minutes >= 60) {
            $this->hours = (int)floor($this->minutes / 60);
            $this->minutes = (int)($this->minutes - ($this->hours * 60));
        }

        if ($this->hours >= $this->hoursPerDay) {
            $this->days = (int)floor($this->hours / $this->hoursPerDay);
            $this->hours = (int)($this->hours - ($this->days * $this->hoursPerDay));
        }

        // If the format does not contain seconds, set seconds to 0
        if ($this->seconds > 0 && !str_contains(strtolower($this->format), 's')) {
            $this->seconds = 0.0;
        }

        return $this;
    }

    /**
     * Validates whether a duration input can be successfully parsed.
     *
     * This static method checks if the provided duration string or numeric value
     * can be successfully parsed by creating a temporary instance and attempting to parse it.
     *
     * @param float|int|string $duration Duration input to validate
     * @return bool True if the duration can be parsed, false otherwise
     */
    public static function valid(float|int|string $duration): bool
    {
        try {
            $temp = new self();
            $result = $temp->parse($duration);
            return $result !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Defines the data that should be serialized to JSON.
     *
     * This method is invoked automatically when the object is passed to `json_encode()`.
     * It delegates the serialization process to the `toArray()` method to provide a structured representation.
     *
     * @return array The array representation of the TimeDuration instance.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Converts the TimeDuration instance to an associative array.
     *
     * The array includes:
     * - Total duration in seconds.
     * - Decomposed values (days, hours, minutes, seconds).
     * - A formatted string representation of the duration.
     * - A human-readable version of the duration.
     *
     * This structure is used for JSON serialization and other external representations.
     *
     * @return array{
     *     seconds: float|int,
     *     values: array{days: int, hours: int, minutes: int, seconds: float|int},
     *     formatted: string,
     *     humanized: string
     * }
     */
    public function toArray(): array
    {
        return [
            'seconds' => $this->toSeconds(),
            'values' => [
                'days' => $this->days,
                'hours' => $this->hours,
                'minutes' => $this->minutes,
                'seconds' => $this->seconds,
            ],
            'formatted' => $this->format(),
            'humanized' => $this->humanize(),
        ];
    }

    /**
     * Returns the duration as a number of seconds.
     *
     * For example, one hour and 42 minutes would be "6120."
     *
     * @param float|int|string|null $duration A string or number, representing a duration
     * @param int|null $precision Number of decimal digits to round to. If set to false, the number is not rounded.
     */
    public function toSeconds(float|int|string|null $duration = null, ?int $precision = null): float|int
    {
        if ($duration !== null) {
            $this->parse($duration);
        }

        $seconds = ($this->days * $this->hoursPerDay * self::HOUR)
            + ($this->hours * self::HOUR)
            + ($this->minutes * self::MINUTE)
            + $this->seconds;

        return $precision !== null ? round($seconds, $precision) : $seconds;
    }

    /**
     * Formats the time duration into a custom string based on a tokenized pattern.
     *
     * Supported tokens include:
     * - `d` / `dd`   : Number of days (non-padded / zero-padded)
     * - `h` / `hh`   : Hours within a day
     * - `H` / `HH`   : Total hours (including days × hoursPerDay)
     * - `m` / `mm`   : Minutes
     * - `s` / `ss`   : Seconds with original decimal value (non-padded / padded)
     * - `S` / `SS`   : Seconds rounded to integer (non-padded / padded)
     *
     * Throws an exception if both `d` (days) and `H` (total hours) are used in the same pattern,
     * as they represent conflicting perspectives of the same duration.
     *
     * If no pattern is provided, it defaults to `$this->format`.
     *
     * Examples:
     * - `hh:mm:ss` → `01:05:30`
     * - `H:mm`     → `29:15`
     * - `d hh:mm`  → `1 05:30`
     *
     * @param string|null $pattern Custom formatting pattern using tokens.
     *
     * @return string The formatted duration string.
     *
     * @throws InvalidArgumentException If both 'd' and 'H' tokens are used together.
     */
    public function format(?string $pattern = null): string
    {
        if ($pattern !== null && str_contains($pattern, 'd') && str_contains($pattern, 'H')) {
            throw new InvalidArgumentException('Invalid format pattern. "d" and "H" cannot be used together.');
        }

        $pattern ??= $this->format;

        $replacements = [
            'dd' => str_pad((string)$this->days, 2, '0', STR_PAD_LEFT),
            'd' => (string)$this->days,
            'hh' => str_pad((string)$this->hours, 2, '0', STR_PAD_LEFT),
            'h' => (string)$this->hours,
            'HH' => str_pad((string)($this->hours + $this->days * $this->hoursPerDay), 2, '0', STR_PAD_LEFT),
            'H' => (string)($this->hours + $this->days * $this->hoursPerDay),
            'mm' => str_pad((string)$this->minutes, 2, '0', STR_PAD_LEFT),
            'm' => (string)$this->minutes,
            'ss' => str_pad((string)$this->seconds, 2, '0', STR_PAD_LEFT),
            's' => (string)$this->seconds,
            'SS' => str_pad(number_format($this->seconds, 0, '.', ''), 2, '0', STR_PAD_LEFT),
            'S' => number_format($this->seconds, 0, '.', ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    public function toIso8601(): string
    {
        $dateDesignators = '';
        $timeDesignators = '';

        if ($this->days > 0) {
            $dateDesignators .= $this->days . 'D';
        }

        if ($this->hours > 0) {
            $timeDesignators .= $this->hours . 'H';
        }

        if ($this->minutes > 0) {
            $timeDesignators .= $this->minutes . 'M';
        }

        if ($this->seconds > 0 || ($dateDesignators === '' && $timeDesignators === '')) {
            $seconds = strpos((string)$this->seconds, '.') !== false
                ? rtrim(rtrim((string)$this->seconds, '0'), '.')
                : (string)$this->seconds;

            $timeDesignators .= $seconds . 'S';
        }

        if ($timeDesignators !== '') {
            $timeDesignators = 'T' . $timeDesignators;
        }

        $isoDuration = 'P' . $dateDesignators . $timeDesignators;

        return $isoDuration === 'P' ? 'PT0S' : $isoDuration;
    }

    /**
     * Returns the duration as a human-readable string.
     *
     * For example, one hour and 42 minutes would be "1h 42m"
     *
     * @param float|int|string|null $duration A string or number, representing a duration
     */
    public function humanize(float|int|string|null $duration = null): string
    {
        if (null !== $duration) {
            $this->parse($duration);
        }

        $output = '';

        if ($this->seconds > 0 || ($this->seconds === 0.0 && $this->minutes === 0 && $this->hours === 0 && $this->days === 0)) {
            $output .= $this->seconds . 's';
        }

        if ($this->minutes > 0) {
            $output = $this->minutes . 'm ' . $output;
        }

        if ($this->hours > 0) {
            $output = $this->hours . 'h ' . $output;
        }

        if ($this->days > 0) {
            $output = $this->days . 'd ' . $output;
        }

        $output = trim($output);
        $this->reset();

        return $output;
    }

    /**
     * Returns the duration as an number of minutes.
     *
     * For example, one hour and 42 minutes would be "102" minutes
     *
     * @param float|int|string|null $duration A string or number, representing a duration
     * @param bool|int $precision Number of decimal digits to round to. If set to false, the number is not rounded.
     */
    public function toMinutes(float|int|string|null $duration = null, bool|int $precision = false): float|int
    {
        $seconds = $this->toSeconds($duration);

        $minutes = $seconds / 60;

        return $precision !== false ? round($minutes, (int)$precision) : $minutes;
    }

    /**
     * Returns the string representation of the TimeDuration instance.
     *
     * This magic method allows the object to be automatically converted to a string,
     * typically when echoed or concatenated. It delegates the formatting to the
     * `format()` method, using the default pattern.
     *
     * Example:
     *     (string) $duration → "01:30:00" (depending on default format)
     *
     * @return string The formatted string representation of the duration.
     */
    public function __toString(): string
    {
        return $this->format();
    }
}
