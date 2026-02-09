<?php

use App\Models\Todo;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page from todos', function () {
    $this->get(route('todos.index'))->assertRedirect(route('login'));
});

test('authenticated users can view the todos index', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('todos.index'))->assertOk();
});

test('todos index only shows the authenticated user todos', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Todo::factory()->for($user)->create(['title' => 'My Todo']);
    Todo::factory()->for($otherUser)->create(['title' => 'Other Todo']);

    $this->actingAs($user);

    $response = $this->get(route('todos.index'));

    $response->assertOk();
    $response->assertSee('My Todo');
    $response->assertDontSee('Other Todo');
});

test('authenticated users can create a todo', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('startCreating')
        ->set('title', 'Buy groceries')
        ->call('createTodo')
        ->assertHasNoErrors();

    expect($user->todos()->count())->toBe(1);
    expect($user->todos()->first()->title)->toBe('Buy groceries');
});

test('authenticated users can create a todo with due date and recurrence', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('startCreating')
        ->set('title', 'Weekly review')
        ->set('dueDate', '2026-03-01')
        ->set('recurrence', 'weekly')
        ->call('createTodo')
        ->assertHasNoErrors();

    $todo = $user->todos()->first();

    expect($todo->title)->toBe('Weekly review');
    expect($todo->due_date->format('Y-m-d'))->toBe('2026-03-01');
    expect($todo->recurrence)->toBe('weekly');
});

test('todo creation requires a title', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('startCreating')
        ->set('title', '')
        ->call('createTodo')
        ->assertHasErrors(['title']);

    expect($user->todos()->count())->toBe(0);
});

test('authenticated users can update a todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('editTodo', $todo->id)
        ->set('title', 'Updated Title')
        ->set('dueDate', '2026-04-15')
        ->set('recurrence', 'monthly')
        ->call('updateTodo')
        ->assertHasNoErrors();

    $todo->refresh();

    expect($todo->title)->toBe('Updated Title');
    expect($todo->due_date->format('Y-m-d'))->toBe('2026-04-15');
    expect($todo->recurrence)->toBe('monthly');
});

test('users cannot edit todos belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->for($otherUser)->create(['title' => 'Original Title']);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('editTodo', $todo->id);

    expect($todo->fresh()->title)->toBe('Original Title');
});

test('toggling a non-recurring todo marks it as completed', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['is_completed' => false]);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('toggleComplete', $todo->id);

    $todo->refresh();

    expect($todo->is_completed)->toBeTrue();
    expect($todo->completed_at)->not->toBeNull();
    expect($user->todos()->count())->toBe(1);
});

test('toggling a completed todo marks it as incomplete', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create(['is_completed' => true, 'completed_at' => now()]);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('toggleComplete', $todo->id);

    $todo->refresh();

    expect($todo->is_completed)->toBeFalse();
    expect($todo->completed_at)->toBeNull();
});

test('completing a recurring todo creates the next occurrence', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create([
        'title' => 'Weekly standup',
        'is_completed' => false,
        'due_date' => '2026-03-01',
        'recurrence' => 'weekly',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('toggleComplete', $todo->id);

    $todo->refresh();

    expect($todo->is_completed)->toBeTrue();
    expect($user->todos()->count())->toBe(2);

    $nextTodo = $user->todos()->where('is_completed', false)->first();

    expect($nextTodo->title)->toBe('Weekly standup');
    expect($nextTodo->due_date->format('Y-m-d'))->toBe('2026-03-08');
    expect($nextTodo->recurrence)->toBe('weekly');
});

test('completing a daily recurring todo advances by one day', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create([
        'due_date' => '2026-03-01',
        'recurrence' => 'daily',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('toggleComplete', $todo->id);

    $nextTodo = $user->todos()->where('is_completed', false)->first();

    expect($nextTodo->due_date->format('Y-m-d'))->toBe('2026-03-02');
});

test('completing a monthly recurring todo advances by one month', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create([
        'due_date' => '2026-03-15',
        'recurrence' => 'monthly',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('toggleComplete', $todo->id);

    $nextTodo = $user->todos()->where('is_completed', false)->first();

    expect($nextTodo->due_date->format('Y-m-d'))->toBe('2026-04-15');
});

test('completing a yearly recurring todo advances by one year', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create([
        'due_date' => '2026-03-15',
        'recurrence' => 'yearly',
    ]);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('toggleComplete', $todo->id);

    $nextTodo = $user->todos()->where('is_completed', false)->first();

    expect($nextTodo->due_date->format('Y-m-d'))->toBe('2027-03-15');
});

test('authenticated users can delete a todo', function () {
    $user = User::factory()->create();
    $todo = Todo::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('deleteTodo', $todo->id)
        ->assertHasNoErrors();

    expect($todo->fresh())->toBeNull();
});

test('users cannot delete todos belonging to other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $todo = Todo::factory()->for($otherUser)->create();

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->call('deleteTodo', $todo->id);

    expect($todo->fresh())->not->toBeNull();
});

test('search filters todos by title', function () {
    $user = User::factory()->create();

    Todo::factory()->for($user)->create(['title' => 'Buy groceries']);
    Todo::factory()->for($user)->create(['title' => 'Call dentist']);

    $this->actingAs($user);

    Livewire::test('pages::todos.index')
        ->set('search', 'groceries')
        ->assertSee('Buy groceries')
        ->assertDontSee('Call dentist');
});
