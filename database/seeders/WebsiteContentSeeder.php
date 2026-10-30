<?php

namespace Database\Seeders;

use App\Models\TourPackage;
use App\Models\WebsiteCar;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cars = [
            ['dzire', 'Swift Dzire', 'Sedan · 4 seater', '4+1', 'Petrol / CNG', '₹1,800', 'Daily city and airport', '/images/swift-dzire-on-rent-in-garia-kolkata.webp', 'Swift Dzire on rent in Garia, Kolkata — silver sedan', 1200, 668, 'The car most Garia and Sonarpur families book for a city day, a hospital visit, or a quiet airport drop.'],
            ['ertiga', 'Ertiga', 'MPV · 6 seater', '6+1', 'Petrol / CNG', '₹2,500', 'Family and small groups', '/images/ertiga-on-rent-in-sonarpur-kolkata.webp', 'Ertiga on rent in Sonarpur, Kolkata — family MPV', 1200, 675, 'Six seats and a proper boot. Right for a family outing to Digha or a full day of drops around South Kolkata.'],
            ['innova', 'Innova Crysta', 'SUV · 7 seater', '7+1', 'Diesel', '₹3,200', 'Outstation and wedding', '/images/innova-crysta-on-rent-in-garia-kolkata.webp', 'Innova Crysta on rent in Garia, Kolkata — silver SUV', 1200, 567, 'The outstation and wedding car. Comfortable on the Puri and Darjeeling roads, with a verified local driver.'],
            ['tempo', 'Tempo Traveller', '12 / 16 / 20 seater', '12–20', 'Diesel', '₹5,500', 'Group tours', '/images/tempo-traveller-on-rent-in-south-kolkata.webp', 'Tempo Traveller on rent in South Kolkata for group tours', 1200, 837, 'When the whole family or office travels together. Ask which seating you need — 12, 16 or 20.'],
        ];

        foreach ($cars as [$slug, $name, $type, $seats, $fuel, $price, $bestFor, $image, $alt, $width, $height, $blurb]) {
            WebsiteCar::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'type' => $type, 'seats' => $seats, 'fuel' => $fuel, 'local_label' => $price, 'best_for' => $bestFor, 'image_url' => $image, 'image_alt' => $alt, 'image_width' => $width, 'image_height' => $height, 'blurb' => $blurb, 'sort_order' => array_search($slug, ['dzire', 'ertiga', 'innova', 'tempo'], true), 'is_published' => true]);
        }

        $tours = [
            ['heritage', 'Kolkata heritage day', '8 hours · inside the city', '₹1,800', 'Swift Dzire', '/images/howrah-bridge-hero.jpg', 'Howrah Bridge at night, the opening stop on a Kolkata heritage day by car', ['Howrah Bridge', 'Victoria Memorial', 'Indian Museum', 'Prinsep Ghat'], 'One city package from Garia, Sonarpur or Kamdahari. The driver waits while you walk. You pay the 8 hour / 80 km fare, not a separate sightseeing markup.'],
            ['temple', 'Temple circuit', '8–10 hours · city', '₹1,800', 'Dzire or Ertiga', '/images/dakshineswar-kali-temple-kolkata.webp', 'Dakshineswar Kali Temple, a morning temple run from South Kolkata', ['Dakshineswar', 'Belur Math', 'Kalighat'], 'An early start so Dakshineswar is quieter. Belur is on the same bank. Kalighat is on the way home to South Kolkata.'],
            ['newtown', 'Eco Park and New Town', '8 hours · family', '₹2,500', 'Ertiga', '/images/eco-park-new-town-kolkata.webp', 'Eco Park in New Town, a family day by hired car', ['Eco Park', 'Biswa Bangla', 'New Town'], 'The family car. Six seats when grandparents come along. Same 8 hour city rule, Ertiga rate.'],
            ['digha', 'Digha', 'Day or 1 night · ~185 km', '₹12/km', 'Dzire, Ertiga or Innova', '/images/prinsep-ghat-kolkata.webp', 'Hooghly riverfront in Kolkata, the start of an outstation run toward Digha', ['Dawn pickup', 'Both ways', 'Toll at actuals'], 'About 185 km one way, charged both ways — roughly 370 km — plus ₹300 driver allowance and toll receipts. We leave from South Kolkata before sunrise.'],
            ['mandarmani', 'Mandarmani', '1 night', '₹12/km', 'Ertiga or Innova Crysta', '/images/ertiga-on-rent-in-sonarpur-kolkata.webp', 'Ertiga on rent in Sonarpur for a Mandarmani overnight', ['Sonarpur start', 'Night halt', 'Driver allowance'], 'Same per-kilometre rule as Digha. The night halt and driver allowance are named on the call before the ₹1,000 token holds the date.'],
            ['puri', 'Puri', '2 nights', '₹12/km', 'Innova Crysta', '/images/innova-crysta-on-rent-in-garia-kolkata.webp', 'Innova Crysta on rent in Garia for a Puri trip', ['Innova Crysta', 'Per day allowance', 'No extra km penalty'], 'The usual Puri car. Kilometres both ways, ₹300 a day for the driver, tolls at the receipt. After 300 km in a day we do not stack a second per-km charge.'],
            ['hills', 'Darjeeling and Dooars', '3–5 nights', 'Quoted', 'Innova Crysta', '/images/victoria-memorial-kolkata.webp', 'Victoria Memorial, Kolkata — the city you leave for Darjeeling and the Dooars', ['Hill permit', 'Night halts', 'Written split'], 'Hill permit and each night halt go into the quote before you pay the token. We do not discover them on the road.'],
            ['oneway', 'One-way drop', 'Durgapur, Asansol, Siliguri', 'One side only', 'Any car in the fleet', '/images/swift-dzire-on-rent-in-garia-kolkata.webp', 'Swift Dzire on rent in Garia for a one-way drop', ['No return km', 'Fixed on the call', 'Token ₹1,000'], 'When the car does not come back with you. You pay the one-way fare we read out, not a return.'],
        ];

        foreach ($tours as $sortOrder => [$slug, $name, $duration, $price, $car, $image, $alt, $stops, $description]) {
            TourPackage::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'duration' => $duration, 'price_label' => $price, 'car' => $car, 'image_url' => $image, 'image_alt' => $alt, 'stops' => $stops, 'description' => $description, 'sort_order' => $sortOrder, 'is_published' => true]);
        }
    }
}
