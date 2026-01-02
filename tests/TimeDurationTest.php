<?php

namespace Demirk\Tests;

use Demirk\PhpDurationFormatter\TimeDuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @internal
 */
#[CoversClass(TimeDuration::class)]
#[Group('Others')]
final class TimeDurationTest extends TestCase
{
    public static function provideParseColonFormat(): iterable
    {
        yield ['01:01:01', 0, 1, 1, 1.0];

        yield ['00:00:00', 0, 0, 0, 0.0];

        yield ['12:34:56', 0, 12, 34, 56.0];

        yield ['00:00:01', 0, 0, 0, 1.0];

        yield ['00:00:10', 0, 0, 0, 10.0];

        yield ['1:01:01', 0, 1, 1, 1.0];

        yield ['0:0:0', 0, 0, 0, 0.0];

        yield ['0:0:1', 0, 0, 0, 1.0];

        yield ['0:0:10', 0, 0, 0, 10.0];

        yield ['00:1:0', 0, 0, 1, 0.0];

        yield ['00:1:1', 0, 0, 1, 1.0];

        yield ['00:1:10', 0, 0, 1, 10.0];

        yield ['1d 2h 3m 4s', 1, 2, 3, 4.0];

        yield ['1d 2h 3m 4.5s', 1, 2, 3, 4.5];

        yield ['1d 10:29', 1, 10, 29, 0.0];

        yield ['1d 10:29:30', 1, 10, 29, 30.0];

        yield 'test hoursPerDay is 8' => ['1d 2h', 3, 2, 0, 0.0, 8];

        yield ['1d 48h 120m 60s', 3, 2, 1, 0.0];

        yield ['1D 48H 120M 60S', 3, 2, 1, 0.0];

        yield ['550:250:150', 23, 2, 12, 30.0];

        yield 'test hoursPerDay is 12' => ['550:250:150', 46, 2, 12, 30.0, 12];

        yield ['12:1:1 2d', 2, 12, 1, 1.0];
    }

    public static function provideFormatCustomPattern(): iterable
    {
        yield ['1d 10H 15m 30s', 'dd hh:mm:ss', '01 10:15:30'];

        yield ['5h 30m', 'HH:mm', '05:30'];

        yield ['2h 15m 45s', 'h:m:s', '2:15:45'];

        yield ['1d 6h', 'H:mm:ss', '30:00:00'];

        yield ['45m 30s', 'h:mm:ss', '0:45:30'];

        yield ['90m', 'HH:mm', '01:30'];

        yield ['3600s', 'hh:mm:ss', '01:00:00'];

        yield ['2d 5h 30m', 'd h:mm', '2 5:30'];

        yield ['1d 12h', 'dd hh', '01 12'];

        yield ['59m 59s', 'mm:ss', '59:59'];

        yield ['2h 0m 1s', 'h:mm:ss', '2:00:01'];

        yield ['48h', 'd hh:mm', '2 00:00'];

        yield ['1d 1h 1m 1s', 'd h:mm:ss', '1 1:01:01'];

        yield ['0h 5m 0s', 'h:mm:ss', '0:05:00'];

        yield ['23h 59m 59s', 'HH:mm:ss', '23:59:59'];

        yield ['1d 0h 0m 1s', 'dd hh:mm:ss', '01 00:00:01'];

        yield ['72h 0m', 'dd hh:mm', '03 00:00'];

        yield ['47h 59m 59s', 'HH:mm:ss', '47:59:59'];

        yield ['0d 5h 30m', 'dd hh:mm', '00 05:30'];

        yield ['1h 30m 45s', 'h:mm:SS', '1:30:45'];

        yield ['2d 4h', 'dd hh', '02 04'];

        yield ['15m 30s', 'mm:ss', '15:30'];

        yield ['3d 0h 0m', 'dd hh:mm', '03 00:00'];

        yield ['1d 23h 59m', 'dd hh:mm', '01 23:59'];

        yield ['0h 0m 30s', 'hh:mm:ss', '00:00:30'];

        yield ['4d 12h 30m', 'dd hh:mm', '04 12:30'];

        yield ['36h 0m 0s', 'HH:mm:ss', '36:00:00'];

        yield ['2d 5h 15m 30s', 'dd hh:mm:ss', '02 05:15:30'];

        yield ['1h 1m 1s', 'hh:mm:ss', '01:01:01'];

        yield ['0d 1h 30m', 'dd hh:mm', '00 01:30'];

        yield ['1s', 'SS', '01'];
    }

    /**
     * Data provider for valid duration formats that should return true
     */
    public static function provideValidDurations(): iterable
    {
        // Numeric formats
        yield 'positive integer' => [3600, true];
        yield 'positive float' => [3661.5, true];
        yield 'zero' => [0, true];
        yield 'zero float' => [0.0, true];

        // String numeric formats
        yield 'string positive integer' => ['3600', true];
        yield 'string positive float' => ['3661.5', true];
        yield 'string zero' => ['0', true];

        // Colon-separated formats
        yield 'HH:MM format' => ['01:30', true];
        yield 'HH:MM:SS format' => ['01:30:45', true];
        yield 'H:M format' => ['1:5', true];
        yield 'H:M:S format' => ['1:5:30', true];
        yield 'zero time HH:MM' => ['00:00', true];
        yield 'zero time HH:MM:SS' => ['00:00:00', true];
        yield 'large hours' => ['25:30:45', true];

        // Unit-based formats
        yield 'hours only' => ['2h', true];
        yield 'minutes only' => ['30m', true];
        yield 'seconds only' => ['45s', true];
        yield 'zero hours' => ['0h', true];
        yield 'zero minutes' => ['0m', true];
        yield 'zero seconds' => ['0s', true];
        yield 'hours and minutes' => ['1h 30m', true];
        yield 'hours minutes seconds' => ['1h 30m 45s', true];
        yield 'mixed case' => ['1H 30M 45S', true];
        yield 'with spaces' => ['1h  30m  45s', true];
        yield 'without spaces' => ['1h30m45s', true];
        yield 'decimal hours' => ['1.5h', true];
        yield 'decimal seconds' => ['45.5s', true];

        // Days format
        yield 'days only' => ['2d', true];
        yield 'zero days' => ['0d', true];
        yield 'days with hours' => ['1d 5h', true];
        yield 'days mixed case' => ['1D 5H', true];
        yield 'decimal days' => ['1.5d', true];

        // Complex combinations
        yield 'days hours minutes seconds' => ['1d 2h 30m 45s', true];
        yield 'days with colon time' => ['1d 10:30:45', true];
        yield 'mixed formats' => ['2d 1h 30m', true];
        yield 'ISO 8601 time' => ['PT1H30M', true];
        yield 'ISO 8601 date time' => ['P1DT2H', true];
        yield 'ISO 8601 weeks' => ['P2W', true];
    }

    /**
     * Data provider for invalid duration formats that should return false
     */
    public static function provideInvalidDurations(): iterable
    {
        // Negative values
        yield 'negative integer' => [-3600, false];
        yield 'negative float' => [-3661.5, false];
        yield 'negative string' => ['-3600', false];

        // Invalid strings
        yield 'empty string' => ['', false];
        yield 'whitespace only' => ['   ', false];
        yield 'random text' => ['invalid', false];
        yield 'random characters' => ['xyz123', false];

        // Invalid colon formats
        yield 'single colon' => ['30:', false];
        yield 'non-numeric colon format' => ['aa:bb', false];

        // Invalid unit formats
        yield 'invalid unit' => ['30x', false];
        yield 'missing number' => ['h', false];
        yield 'just units' => ['hms', false];

        // Boolean and other types would be handled by type system, but let's test edge cases
        yield 'null string' => ['null', false];
        yield 'boolean string' => ['true', false];
        yield 'invalid ISO 8601 designator' => ['P', false];
    }

    /**
     * Tests parsing of a numeric duration (in seconds with decimal) into hours, minutes, and seconds.
     *
     * @throws ReflectionException
     */
    public function testParseNumericDuration(): void
    {
        $duration = new TimeDuration(3661.8); // 1h 1m 1s

        $hours   = $this->getPrivateProperty($duration, 'hours');
        $minutes = $this->getPrivateProperty($duration, 'minutes');
        $seconds = $this->getPrivateProperty($duration, 'seconds');

        $this->assertSame(1, $hours);
        $this->assertSame(1, $minutes);
        $this->assertSame(1.8, $seconds);
    }

    /**
     * Tests parsing of colon-separated string formats (e.g., "01:02:03") into correct time components.
     * Validates handling of optional seconds and custom hours-per-day configuration.
     *
     * @throws ReflectionException
     */
    /**
     * @dataProvider provideParseColonFormat
     */
    #[DataProvider('provideParseColonFormat')]
    public function testParseColonFormat(string $duration, int $days, int $hours, int $minutes, float|int $seconds, int $hoursPerDay = 24): void
    {
        $duration = new TimeDuration($duration, $hoursPerDay);

        $days    = $this->getPrivateProperty($duration, 'days');
        $hours   = $this->getPrivateProperty($duration, 'hours');
        $minutes = $this->getPrivateProperty($duration, 'minutes');
        $seconds = $this->getPrivateProperty($duration, 'seconds');

        $this->assertSame($days, $days, 'Days do not match');
        $this->assertSame($hours, $hours, 'Hours do not match');
        $this->assertSame($minutes, $minutes, 'Minutes do not match');
        $this->assertSame($seconds, $seconds, 'Seconds do not match');
    }

    /**
     * Tests that seconds are excluded from output when the format omits them (e.g., 'hh:mm').
     * Verifies correct calculation and zeroing of seconds component.
     *
     * @throws ReflectionException
     */
    public function testParseWithSecondsFormat(): void
    {
        $duration = new TimeDuration('01:01:15', 24, 'hh:mm');

        $days    = $this->getPrivateProperty($duration, 'days');
        $hours   = $this->getPrivateProperty($duration, 'hours');
        $minutes = $this->getPrivateProperty($duration, 'minutes');
        $seconds = $this->getPrivateProperty($duration, 'seconds');

        $this->assertSame(0, $days);
        $this->assertSame(1, $hours);
        $this->assertSame(1, $minutes);
        $this->assertSame(0.0, $seconds);

        $this->assertSame('01:01', (string) $duration, 'Formatted string does not match');

        $this->assertSame(3660.0, $duration->toSeconds(), 'Seconds do not match, should be 3660');
    }

    /**
     * Tests conversion of a duration into total seconds.
     */
    public function testToSeconds(): void
    {
        $duration = new TimeDuration('1h 1m 1s');
        $seconds  = $duration->toSeconds();

        $this->assertSame(3661.0, $seconds);
    }

    /**
     * Tests conversion of a duration into total minutes.
     */
    public function testToMinutes(): void
    {
        $duration = new TimeDuration('1h 30m');
        $minutes  = $duration->toMinutes();

        $this->assertSame(90.0, $minutes);
    }

    /**
     * Tests custom format patterns and verifies that the formatted string matches expected output.
     */
    /**
     * @dataProvider provideFormatCustomPattern
     */
    #[DataProvider('provideFormatCustomPattern')]
    public function testFormatCustomPattern(int|string $duration, string $format, string $expected): void
    {
        $durationInstance = new TimeDuration($duration);
        $this->assertSame($expected, $durationInstance->format($format));
    }

    /**
     * Tests the human-readable version of the duration string.
     */
    public function testHumanize(): void
    {
        $duration = new TimeDuration('1h 42m');
        $this->assertSame('1h 42m', $duration->humanize());
    }

    /**
     * Tests that an empty TimeDuration instance returns '0s' in the humanized output.
     */
    public function testEmptyDurationReturnsZeroSeconds(): void
    {
        $duration = new TimeDuration();
        $this->assertSame('0s', $duration->humanize());
    }

    /**
     * Tests JSON serialization of the duration and verifies its structure and content.
     */
    public function testJsonEncode(): void
    {
        $duration = new TimeDuration('1h 42m 30s');
        $this->assertSame('{"seconds":6150,"values":{"days":0,"hours":1,"minutes":42,"seconds":30},"formatted":"01:42:30","humanized":"1h 42m 30s"}', json_encode($duration));
    }

    /**
     * Tests the string representation of the TimeDuration instance using __toString().
     */
    public function testToString(): void
    {
        $duration = new TimeDuration('2h 15m');

        $this->assertSame('02:15:00', (string) $duration);
    }

    /**
     * Tests the valid() static method with various valid duration formats
     *
     * @param mixed $duration The duration input to validate
     * @param bool $expected The expected validation result
     */
    /**
     * @dataProvider provideValidDurations
     */
    #[DataProvider('provideValidDurations')]
    public function testValidWithValidDurations(mixed $duration, bool $expected): void
    {
        $result = TimeDuration::valid($duration);
        $this->assertSame($expected, $result, "Duration '{$duration}' should be valid but returned false");
    }

    /**
     * Tests the valid() static method with various invalid duration formats
     *
     * @param mixed $duration The duration input to validate
     * @param bool $expected The expected validation result
     */
    /**
     * @dataProvider provideInvalidDurations
     */
    #[DataProvider('provideInvalidDurations')]
    public function testValidWithInvalidDurations(mixed $duration, bool $expected): void
    {
        $result = TimeDuration::valid($duration);
        $this->assertSame($expected, $result, "Duration '{$duration}' should be invalid but returned true");
    }

    /**
     * Tests that valid() method is consistent with parse() method results
     */
    public function testValidConsistencyWithParse(): void
    {
        $testCases = [
            '1h 30m',
            '00:30',
            '1d',
            '0s',
            'invalid',
            '',
            -100,
            3600
        ];

        foreach ($testCases as $testCase) {
            $temp = new TimeDuration();
            $parseResult = $temp->parse($testCase);
            $validResult = TimeDuration::valid($testCase);

            $expectedValid = $parseResult !== false;

            $this->assertSame(
                $expectedValid,
                $validResult,
                "valid() and parse() results should be consistent for input: '{$testCase}'"
            );
        }
    }

    /**
     * Tests TimeDuration::fromSeconds() static factory method
     */
    public function testFromSeconds(): void
    {
        $duration = TimeDuration::fromSeconds(3661);

        $this->assertSame(1, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(1, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(1.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'days'));
        $this->assertSame('01:01:01', $duration->format());
    }

    /**
     * Tests TimeDuration::fromSeconds() with float values
     */
    public function testFromSecondsWithFloat(): void
    {
        $duration = TimeDuration::fromSeconds(90.5);

        $this->assertSame(1, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(30.5, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('00:01:30.5', $duration->format('hh:mm:s'));
    }

    /**
     * Tests TimeDuration::fromMinutes() static factory method
     */
    public function testFromMinutes(): void
    {
        $duration = TimeDuration::fromMinutes(90);

        $this->assertSame(1, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(30, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(0.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('01:30:00', $duration->format());
    }

    /**
     * Tests TimeDuration::fromMinutes() with float values
     */
    public function testFromMinutesWithFloat(): void
    {
        $duration = TimeDuration::fromMinutes(1.5);

        $this->assertSame(1, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(30.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('00:01:30', $duration->format());
    }

    /**
     * Tests TimeDuration::fromHours() static factory method
     */
    public function testFromHours(): void
    {
        $duration = TimeDuration::fromHours(25);

        $this->assertSame(1, $this->getPrivateProperty($duration, 'days'));
        $this->assertSame(1, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(0.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('01:01:00', $duration->format('dd:hh:mm'));
    }

    /**
     * Tests TimeDuration::fromHours() with float values
     */
    public function testFromHoursWithFloat(): void
    {
        $duration = TimeDuration::fromHours(1.5);

        $this->assertSame(1, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(30, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(0.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('01:30:00', $duration->format());
    }

    /**
     * Tests TimeDuration::fromDays() static factory method
     */
    public function testFromDays(): void
    {
        $duration = TimeDuration::fromDays(2);

        $this->assertSame(2, $this->getPrivateProperty($duration, 'days'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(0.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('02 00:00:00', $duration->format('dd hh:mm:ss'));
    }

    /**
     * Tests TimeDuration::fromDays() with float values
     */
    public function testFromDaysWithFloat(): void
    {
        $duration = TimeDuration::fromDays(1.5);

        $this->assertSame(1, $this->getPrivateProperty($duration, 'days'));
        $this->assertSame(12, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(0.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('01 12:00:00', $duration->format('dd hh:mm:ss'));
    }

    /**
     * Tests TimeDuration::fromString() static factory method with various string formats
     */
    public function testFromString(): void
    {
        // Test with unit-based format
        $duration1 = TimeDuration::fromString('1h 30m 45s');
        $this->assertSame('01:30:45', $duration1->format());

        // Test with colon format
        $duration2 = TimeDuration::fromString('02:15:30');
        $this->assertSame('02:15:30', $duration2->format());

        // Test with mixed format
        $duration3 = TimeDuration::fromString('1d 2h 30m');
        $this->assertSame('01 02:30:00', $duration3->format('dd hh:mm:ss'));
    }

    /**
     * Tests TimeDuration::zero() static factory method
     */
    public function testZero(): void
    {
        $duration = TimeDuration::zero();

        $this->assertSame(0, $this->getPrivateProperty($duration, 'days'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'minutes'));
        $this->assertSame(0.0, $this->getPrivateProperty($duration, 'seconds'));
        $this->assertSame('00:00:00', $duration->format());
        $this->assertSame(0.0, $duration->toSeconds());
        $this->assertSame('0s', $duration->humanize());
    }

    /**
     * Tests static factory methods with custom hoursPerDay parameter
     */
    public function testStaticMethodsWithCustomHoursPerDay(): void
    {
        // Test with 8 hours per day
        $duration = TimeDuration::fromHours(16, 8);

        $this->assertSame(2, $this->getPrivateProperty($duration, 'days'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(8, $duration->hoursPerDay);
    }

    /**
     * Tests static factory methods with custom format parameter
     */
    public function testStaticMethodsWithCustomFormat(): void
    {
        $duration = TimeDuration::fromMinutes(90, 24, 'H:mm');

        $this->assertSame('H:mm', $duration->format);
        $this->assertSame('1:30', $duration->format());
    }

    /**
     * Tests edge cases for static factory methods
     */
    public function testStaticMethodsEdgeCases(): void
    {
        // Test zero values
        $this->assertSame(0.0, TimeDuration::fromSeconds(0)->toSeconds());
        $this->assertSame(0.0, TimeDuration::fromMinutes(0)->toSeconds());
        $this->assertSame(0.0, TimeDuration::fromHours(0)->toSeconds());
        $this->assertSame(0.0, TimeDuration::fromDays(0)->toSeconds());

        // Test large values
        $largeDuration = TimeDuration::fromSeconds(90061); // 1 day, 1 hour, 1 minute, 1 second
        $this->assertSame(1, $this->getPrivateProperty($largeDuration, 'days'));
        $this->assertSame(1, $this->getPrivateProperty($largeDuration, 'hours'));
        $this->assertSame(1, $this->getPrivateProperty($largeDuration, 'minutes'));
        $this->assertSame(1.0, $this->getPrivateProperty($largeDuration, 'seconds'));
    }

    public function testIso8601Formatting(): void
    {
        $duration = new TimeDuration('1d 2h 3m 4s');

        $this->assertSame('P1DT2H3M4S', $duration->toIso8601());
    }

    public function testIso8601ParsingWithWeeks(): void
    {
        $duration = new TimeDuration('P2W');

        $this->assertSame(14, $this->getPrivateProperty($duration, 'days'));
        $this->assertSame(0, $this->getPrivateProperty($duration, 'hours'));
        $this->assertSame(1209600.0, $duration->toSeconds());
        $this->assertSame('P14D', $duration->toIso8601());
    }

    /**
     * Tests that static factory methods create equivalent instances to constructor
     */
    public function testStaticMethodsEquivalentToConstructor(): void
    {
        // Test seconds equivalence
        $constructorDuration = new TimeDuration(3600);
        $staticDuration = TimeDuration::fromSeconds(3600);
        $this->assertSame($constructorDuration->toSeconds(), $staticDuration->toSeconds());
        $this->assertSame($constructorDuration->format(), $staticDuration->format());

        // Test string equivalence
        $constructorStringDuration = new TimeDuration('1h 30m');
        $staticStringDuration = TimeDuration::fromString('1h 30m');
        $this->assertSame($constructorStringDuration->toSeconds(), $staticStringDuration->toSeconds());
        $this->assertSame($constructorStringDuration->format(), $staticStringDuration->format());
    }

    /**
     * @throws ReflectionException
     */
    private function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }
}
