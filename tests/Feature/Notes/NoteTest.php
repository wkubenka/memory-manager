<?php

use App\Models\Note;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page from notes', function () {
    $this->get(route('notes.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the notes index', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('notes.index'))->assertOk();
});

test('notes index only shows the authenticated user notes', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Note::factory()->for($user)->create(['title' => 'My Note']);
    Note::factory()->for($otherUser)->create(['title' => 'Other Note']);

    $this->actingAs($user);

    $response = $this->get(route('notes.index'));

    $response->assertOk();
    $response->assertSee('My Note');
    $response->assertDontSee('Other Note');
});

test('authenticated users can view the create note page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('notes.create'))->assertOk();
});

test('authenticated users can create a note', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.create')
        ->set('title', 'Test Note')
        ->set('content', 'Test content for the note.')
        ->call('createNote')
        ->assertHasNoErrors()
        ->assertRedirect(route('notes.index'));

    expect($user->notes()->count())->toBe(1);
    expect($user->notes()->first()->title)->toBe('Test Note');
    expect($user->notes()->first()->content)->toBe('Test content for the note.');
});

test('note creation allows empty title and content', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.create')
        ->set('title', '')
        ->set('content', '')
        ->call('createNote')
        ->assertHasNoErrors()
        ->assertRedirect(route('notes.index'));

    expect($user->notes()->count())->toBe(1);
    expect($user->notes()->first()->title)->toBeEmpty();
    expect($user->notes()->first()->content)->toBeEmpty();
});

test('authenticated users can view the edit note page', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user);

    $this->get(route('notes.edit', $note))->assertOk();
});

test('users cannot edit notes belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->for($otherUser)->create();

    $this->actingAs($user);

    $this->get(route('notes.edit', $note))->assertForbidden();
});

test('authenticated users can update a note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.edit', ['note' => $note])
        ->set('title', 'Updated Title')
        ->set('content', 'Updated content.')
        ->call('updateNote')
        ->assertHasNoErrors();

    $note->refresh();

    expect($note->title)->toBe('Updated Title');
    expect($note->content)->toBe('Updated content.');
});

test('note update allows empty title and content', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.edit', ['note' => $note])
        ->set('title', '')
        ->set('content', '')
        ->call('updateNote')
        ->assertHasNoErrors();

    $note->refresh();

    expect($note->title)->toBeEmpty();
    expect($note->content)->toBeEmpty();
});

test('authenticated users can delete a note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('deleteNote', $note->id)
        ->assertHasNoErrors();

    expect($note->fresh())->toBeNull();
});

test('users cannot delete notes belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->for($otherUser)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('deleteNote', $note->id);

    expect($note->fresh())->not->toBeNull();
});

test('search filters notes by title', function () {
    $user = User::factory()->create();

    Note::factory()->for($user)->create(['title' => 'Grocery List']);
    Note::factory()->for($user)->create(['title' => 'Meeting Notes']);

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->set('search', 'Grocery')
        ->assertSee('Grocery List')
        ->assertDontSee('Meeting Notes');
});

test('search filters notes by content', function () {
    $user = User::factory()->create();

    Note::factory()->for($user)->create(['title' => 'Note A', 'content' => 'Buy milk and eggs']);
    Note::factory()->for($user)->create(['title' => 'Note B', 'content' => 'Schedule dentist appointment']);

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->set('search', 'milk')
        ->assertSee('Note A')
        ->assertDontSee('Note B');
});

test('empty search shows all notes', function () {
    $user = User::factory()->create();

    Note::factory()->for($user)->create(['title' => 'First Note']);
    Note::factory()->for($user)->create(['title' => 'Second Note']);

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->set('search', '')
        ->assertSee('First Note')
        ->assertSee('Second Note');
});
