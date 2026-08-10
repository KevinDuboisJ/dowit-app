<?php

namespace Tests\Unit\Services;

use App\Enums\TeamEnum;
use App\Models\Team;
use App\Models\User;
use App\Services\DeviceSelectionService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DeviceSelectionServiceTest extends TestCase
{
    public function test_device_selection_is_required_for_a_regular_schoonmaak_ca_user(): void
    {
        $user = $this->userForTeam(TeamEnum::SchoonmaakCA);

        $this->assertTrue(app(DeviceSelectionService::class)->isRequiredFor($user));
    }

    public function test_device_selection_is_required_for_a_regular_schoonmaak_cd_user(): void
    {
        $user = $this->userForTeam(TeamEnum::SchoonmaakCD);

        $this->assertTrue(app(DeviceSelectionService::class)->isRequiredFor($user));
    }

    private function userForTeam(TeamEnum $team): User
    {
        $teamModel = new Team();
        $teamModel->setAttribute('id', $team->value);

        $user = new User();
        $user->setRelation('teams', new Collection([$teamModel]));

        return $user;
    }
}
