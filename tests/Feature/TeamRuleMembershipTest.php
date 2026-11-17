<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeamRuleMembershipTest extends TestCase
{
    public function test_removing_the_last_rule_removes_members_only_from_that_team(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->text('autoassign_rules')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('department_id')->nullable();
            $table->integer('profession_id')->nullable();
            $table->boolean('is_active');
        });
        Schema::create('team_user', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        DB::table('teams')->insert([
            ['id' => 2, 'autoassign_rules' => json_encode([['department_id' => 'DEPT001', 'profession_id' => null]])],
            ['id' => 3, 'autoassign_rules' => null],
        ]);
        DB::table('users')->insert([
            ['id' => 2, 'department_id' => 1, 'profession_id' => 1, 'is_active' => true],
            ['id' => 3, 'department_id' => 1, 'profession_id' => 1, 'is_active' => false],
        ]);
        DB::table('team_user')->insert([
            ['team_id' => 2, 'user_id' => 2],
            ['team_id' => 2, 'user_id' => 3],
            ['team_id' => 3, 'user_id' => 2],
        ]);

        $team = Team::findOrFail(2);
        $team->autoassign_rules = [];
        $team->save();

        $this->assertNull($team->fresh()->autoassign_rules);
        $this->assertSame(0, $team->users()->active()->count());
        $this->assertSame(0, DB::table('team_user')->where('team_id', 2)->count());
        $this->assertSame(1, DB::table('team_user')->where('team_id', 3)->count());
    }
}
