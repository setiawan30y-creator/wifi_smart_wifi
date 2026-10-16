<?php

namespace Tests\Unit;

use App\Support\MapsCoordinates;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapsCoordinatesTest extends TestCase
{
    public function test_reads_common_google_maps_formats(): void
    {
        $cases = [
            'https://www.google.com/maps/@-6.2088,106.8456,17z' => [-6.2088, 106.8456],
            'https://maps.google.com/?q=-6.2080,106.8450' => [-6.2080, 106.8450],
            'https://www.google.com/maps/place/Lokasi/@-6.21,106.84,17z/data=!3d-6.2101!4d106.8402' => [-6.21, 106.84],
            '-6.123456, 106.654321' => [-6.123456, 106.654321],
            'https://www.google.com/maps/search/-6.3001,+106.7002' => [-6.3001, 106.7002],
        ];

        foreach ($cases as $text => [$lat, $lng]) {
            $this->assertSame(['lat' => $lat, 'lng' => $lng], MapsCoordinates::fromText($text), $text);
        }
    }

    public function test_rejects_text_without_valid_coordinates(): void
    {
        $this->assertNull(MapsCoordinates::fromText('Jl. Contoh No. 1'));
        $this->assertNull(MapsCoordinates::fromText('123.456, 999.123'));
        $this->assertNull(MapsCoordinates::fromText(''));
    }

    public function test_resolves_short_link_by_following_google_redirect(): void
    {
        Http::fake([
            'maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/@-6.5,106.9,17z']),
        ]);

        $result = MapsCoordinates::resolve('https://maps.app.goo.gl/abc123');

        $this->assertSame(-6.5, $result['lat']);
        $this->assertSame(106.9, $result['lng']);
    }

    public function test_does_not_fetch_non_google_hosts(): void
    {
        Http::fake();

        $this->assertNull(MapsCoordinates::resolve('http://169.254.169.254/latest?goo.gl'));
        Http::assertNothingSent();
    }
}
