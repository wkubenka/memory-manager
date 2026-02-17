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

test('authenticated users can create a note', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('startCreating')
        ->set('title', 'Test Note')
        ->set('content', 'Test content for the note.')
        ->call('createNote')
        ->assertHasNoErrors();

    expect($user->notes()->count())->toBe(1);
    expect($user->notes()->first()->title)->toBe('Test Note');
    expect($user->notes()->first()->content)->toBe('Test content for the note.');
});

test('note creation allows empty title and content', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('startCreating')
        ->set('title', '')
        ->set('content', '')
        ->call('createNote')
        ->assertHasNoErrors();

    expect($user->notes()->count())->toBe(1);
    expect($user->notes()->first()->title)->toBeEmpty();
    expect($user->notes()->first()->content)->toBeEmpty();
});

test('authenticated users can update a note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('editNote', $note->id)
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

    Livewire::test('pages::notes.index')
        ->call('editNote', $note->id)
        ->set('title', '')
        ->set('content', '')
        ->call('updateNote')
        ->assertHasNoErrors();

    $note->refresh();

    expect($note->title)->toBeEmpty();
    expect($note->content)->toBeEmpty();
});

test('users cannot edit notes belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->for($otherUser)->create(['title' => 'Original Title']);

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('editNote', $note->id);

    expect($note->fresh()->title)->toBe('Original Title');
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

test('pinned notes appear before unpinned notes', function () {
    $user = User::factory()->create();

    Note::factory()->for($user)->create(['title' => 'Unpinned Note']);
    Note::factory()->for($user)->pinned(0)->create(['title' => 'Pinned Note']);

    $this->actingAs($user);

    $this->get(route('notes.index'))
        ->assertOk()
        ->assertSeeInOrder(['Pinned Note', 'Unpinned Note']);
});

test('user can pin a note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('togglePin', $note->id);

    expect($note->fresh()->pin_order)->toBe(0);
});

test('user can unpin a pinned note', function () {
    $user = User::factory()->create();
    $note = Note::factory()->for($user)->pinned(0)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('togglePin', $note->id);

    expect($note->fresh()->pin_order)->toBeNull();
});

test('pinning a note assigns the next available pin order', function () {
    $user = User::factory()->create();

    Note::factory()->for($user)->pinned(0)->create();
    Note::factory()->for($user)->pinned(1)->create();
    $newNote = Note::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('togglePin', $newNote->id);

    expect($newNote->fresh()->pin_order)->toBe(2);
});

test('unpinning a note resequences remaining pinned notes', function () {
    $user = User::factory()->create();

    $note0 = Note::factory()->for($user)->pinned(0)->create();
    $note1 = Note::factory()->for($user)->pinned(1)->create();
    $note2 = Note::factory()->for($user)->pinned(2)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('togglePin', $note1->id);

    expect($note0->fresh()->pin_order)->toBe(0);
    expect($note1->fresh()->pin_order)->toBeNull();
    expect($note2->fresh()->pin_order)->toBe(1);
});

test('pinned notes can be reordered', function () {
    $user = User::factory()->create();

    $noteA = Note::factory()->for($user)->pinned(0)->create(['title' => 'A']);
    $noteB = Note::factory()->for($user)->pinned(1)->create(['title' => 'B']);
    $noteC = Note::factory()->for($user)->pinned(2)->create(['title' => 'C']);

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('reorderPinnedNotes', $noteC->id, 0);

    expect($noteC->fresh()->pin_order)->toBe(0);
    expect($noteA->fresh()->pin_order)->toBe(1);
    expect($noteB->fresh()->pin_order)->toBe(2);
});

test('users cannot pin notes belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->for($otherUser)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('togglePin', $note->id);

    expect($note->fresh()->pin_order)->toBeNull();
});

test('users cannot reorder notes belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $note = Note::factory()->for($otherUser)->pinned(0)->create();

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->call('reorderPinnedNotes', $note->id, 2);

    expect($note->fresh()->pin_order)->toBe(0);
});

test('pinned notes appear first in search results', function () {
    $user = User::factory()->create();

    Note::factory()->for($user)->create(['title' => 'Meeting Agenda']);
    Note::factory()->for($user)->pinned(0)->create(['title' => 'Meeting Notes']);

    $this->actingAs($user);

    Livewire::test('pages::notes.index')
        ->set('search', 'Meeting')
        ->assertSeeInOrder(['Meeting Notes', 'Meeting Agenda']);
});
