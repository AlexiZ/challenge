<?php

namespace App\Tests\Security;

use App\Entity\CityEdition;
use App\Entity\Edition;
use App\Entity\Trip;
use App\Entity\User;
use App\Form\AdminUserType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityFixesTest extends KernelTestCase
{
    public function testCityAdminFormCannotGrantRolesMoveCityOrSetPassword(): void
    {
        $factory = static::getContainer()->get('form.factory');

        $cityAdminForm = $factory->create(AdminUserType::class, new User(), ['city_admin' => true]);
        foreach (['roles', 'city', 'plainPassword'] as $field) {
            self::assertFalse($cityAdminForm->has($field), $field);
        }
        self::assertTrue($cityAdminForm->has('email'));

        $superAdminForm = $factory->create(AdminUserType::class, new User());
        self::assertTrue($superAdminForm->has('roles'));
    }

    #[DataProvider('tripDates')]
    public function testTripDateMustBeWithinEditionAndNotInFuture(string $start, string $end, string $tripDate, bool $valid): void
    {
        $edition = (new Edition())->setStartDate(new \DateTime($start))->setEndDate(new \DateTime($end));
        $trip = (new Trip())
            ->setCityEdition((new CityEdition())->setEdition($edition))
            ->setDistanceKm(5)
            ->setTripDate(new \DateTime($tripDate));

        $violations = static::getContainer()->get(ValidatorInterface::class)->validate($trip);

        self::assertSame($valid, 0 === \count($violations), (string) $violations);
    }

    public static function tripDates(): iterable
    {
        yield 'yesterday, within edition' => ['-10 days', '+10 days', 'yesterday', true];
        yield 'today' => ['-10 days', '+10 days', 'today', true];
        yield 'future, within edition' => ['-10 days', '+10 days', 'tomorrow', false];
        yield 'before edition' => ['-10 days', '+10 days', '-20 days', false];
        yield 'after ended edition' => ['-30 days', '-20 days', '-5 days', false];
    }

    public function testTripsCannotBeEditedOnceEditionIsOver(): void
    {
        $ended = (new Edition())->setStartDate(new \DateTime('-30 days'))->setEndDate(new \DateTime('-20 days'));
        $running = (new Edition())->setStartDate(new \DateTime('-10 days'))->setEndDate(new \DateTime('+10 days'));

        self::assertFalse((new CityEdition())->setEdition($ended)->setTripsEntryOpen(true)->canEditTrips());
        self::assertFalse((new CityEdition())->setEdition($running)->setTripsEntryOpen(false)->canEditTrips());
        self::assertTrue((new CityEdition())->setEdition($running)->setTripsEntryOpen(true)->canEditTrips());
    }
}
