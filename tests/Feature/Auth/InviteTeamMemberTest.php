<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Http\Livewire\TeamMemberManager;
use Laravel\Jetstream\Mail\TeamInvitation;
use Livewire\Livewire;

test('team members can be invited to team', function (): void {
    Mail::fake();

    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
        ->set('addTeamMemberForm', [
            'email' => 'test@example.com',
            'role' => 'admin',
        ])->call('addTeamMember');

    Mail::assertSent(TeamInvitation::class);

    expect($user->currentTeam->fresh()->teamInvitations)->toHaveCount(1)
        ->and($user->currentTeam->teamInvitations()->first())->toBeInstanceOf(App\Models\TeamInvitation::class)
        ->and($user->currentTeam->teamInvitations()->first()->team->id)->toBe($user->currentTeam->id);
})->skip(fn (): bool => ! Features::sendsTeamInvitations(), 'Team invitations not enabled.');

test('team member invitations can be cancelled', function (): void {
    Mail::fake();

    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    // Add the team member...
    $component = Livewire::test(TeamMemberManager::class, ['team' => $user->currentTeam])
        ->set('addTeamMemberForm', [
            'email' => 'test@example.com',
            'role' => 'admin',
        ])->call('addTeamMember');

    $invitationId = $user->currentTeam->fresh()->teamInvitations->first()->id;

    // Cancel the team invitation...
    $component->call('cancelTeamInvitation', $invitationId);

    expect($user->currentTeam->fresh()->teamInvitations)->toHaveCount(0);
})->skip(fn (): bool => ! Features::sendsTeamInvitations(), 'Team invitations not enabled.');
