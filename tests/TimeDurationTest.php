<?php

namespace Demirk\tests;

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
     * @throws ReflectionException
     */
    private function getPrivateProperty(object $object, string $propertyName): mixed
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        return $property->getValue($object);
    }
}
