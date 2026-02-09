<?php

use App\Concerns\TodoValidationRules;
use App\Models\Todo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    use TodoValidationRules;

    #[Url]
    public string $search = '';

    public ?int $editingTodoId = null;
    public bool $creatingTodo = false;
    public string $title = '';
    public ?string $dueDate = null;
    public ?string $recurrence = null;

    /**
     * Start creating a new todo.
     */
    public function startCreating(): void
    {
        $this->editingTodoId = null;
        $this->creatingTodo = true;
        $this->title = '';
        $this->dueDate = null;
        $this->recurrence = null;
    }

    /**
     * Create a new todo.
     */
    public function createTodo(): void
    {
        $validated = $this->validate($this->todoRules());

        Auth::user()->todos()->create([
            'title' => $validated['title'],
            'due_date' => $validated['dueDate'],
            'recurrence' => $validated['recurrence'] ?: null,
        ]);

        $this->cancelEdit();
    }

    /**
     * Start editing a todo.
     */
    public function editTodo(int $todoId): void
    {
        $todo = Auth::user()->todos()->find($todoId);

        if (! $todo) {
            return;
        }

        $this->creatingTodo = false;
        $this->editingTodoId = $todo->id;
        $this->title = $todo->title;
        $this->dueDate = $todo->due_date?->format('Y-m-d');
        $this->recurrence = $todo->recurrence;
    }

    /**
     * Update the todo being edited.
     */
    public function updateTodo(): void
    {
        $validated = $this->validate($this->todoRules());

        Auth::user()->todos()->find($this->editingTodoId)?->update([
            'title' => $validated['title'],
            'due_date' => $validated['dueDate'],
            'recurrence' => $validated['recurrence'] ?: null,
        ]);

        $this->cancelEdit();
    }

    /**
     * Cancel editing or creating.
     */
    public function cancelEdit(): void
    {
        $this->editingTodoId = null;
        $this->creatingTodo = false;
        $this->title = '';
        $this->dueDate = null;
        $this->recurrence = null;
        $this->resetValidation();
    }

    /**
     * Toggle a todo's completion status.
     */
    public function toggleComplete(int $todoId): void
    {
        $todo = Auth::user()->todos()->find($todoId);

        if (! $todo) {
            return;
        }

        if (! $todo->is_completed && $todo->recurrence && $todo->due_date) {
            $nextDueDate = match ($todo->recurrence) {
                'daily' => $todo->due_date->addDay(),
                'weekly' => $todo->due_date->addWeek(),
                'monthly' => $todo->due_date->addMonth(),
                'yearly' => $todo->due_date->addYear(),
            };

            Auth::user()->todos()->create([
                'title' => $todo->title,
                'due_date' => $nextDueDate,
                'recurrence' => $todo->recurrence,
            ]);
        }

        $todo->update([
            'is_completed' => ! $todo->is_completed,
            'completed_at' => ! $todo->is_completed ? now() : null,
        ]);
    }

    /**
     * Delete a todo.
     */
    public function deleteTodo(int $todoId): void
    {
        Auth::user()->todos()->find($todoId)?->delete();
    }

    /**
     * Get the user's todos.
     *
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $query = Auth::user()->todos()->latest();

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        $todos = $query->get();

        return [
            'activeTodos' => $todos->where('is_completed', false)->values(),
            'completedTodos' => $todos->where('is_completed', true)->values(),
        ];
    }
}; ?>

<div class="w-full max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Todos') }}</flux:heading>
            <flux:subheading>{{ __('Manage your tasks and recurring items') }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="startCreating" icon="plus">
            {{ __('New Todo') }}
        </flux:button>
    </div>

    <div class="mb-6">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search todos...')" icon="magnifying-glass" />
    </div>

    @if ($creatingTodo)
        <form wire:submit="createTodo" class="mb-4 rounded-xl border border-neutral-200 p-4 space-y-4 dark:border-neutral-700">
            <flux:input wire:model="title" :placeholder="__('Title')" type="text" x-init="$el.focus()" />
            <div class="flex gap-4">
                <div class="flex-1">
                    <flux:input wire:model="dueDate" :label="__('Due date')" type="date" />
                </div>
                <div class="flex-1">
                    <flux:select wire:model="recurrence" :label="__('Recurrence')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        <flux:select.option value="daily">{{ __('Daily') }}</flux:select.option>
                        <flux:select.option value="weekly">{{ __('Weekly') }}</flux:select.option>
                        <flux:select.option value="monthly">{{ __('Monthly') }}</flux:select.option>
                        <flux:select.option value="yearly">{{ __('Yearly') }}</flux:select.option>
                    </flux:select>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    @endif

    @if ($activeTodos->isEmpty() && $completedTodos->isEmpty() && ! $creatingTodo)
        <div class="rounded-xl border border-neutral-200 p-8 text-center dark:border-neutral-700">
            <flux:heading>{{ __('No todos yet') }}</flux:heading>
            <flux:subheading>{{ __('Create your first todo to get started.') }}</flux:subheading>

            <div class="mt-4">
                <flux:button variant="primary" wire:click="startCreating">
                    {{ __('Create a Todo') }}
                </flux:button>
            </div>
        </div>
    @endif

    @if ($activeTodos->isNotEmpty())
        <div class="space-y-4">
            @foreach ($activeTodos as $todo)
                @if ($editingTodoId === $todo->id)
                    <form wire:submit="updateTodo" class="rounded-xl border border-neutral-200 p-4 space-y-4 dark:border-neutral-700">
                        <flux:input wire:model="title" :placeholder="__('Title')" type="text" x-init="$el.focus()" />
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <flux:input wire:model="dueDate" :label="__('Due date')" type="date" />
                            </div>
                            <div class="flex-1">
                                <flux:select wire:model="recurrence" :label="__('Recurrence')">
                                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                                    <flux:select.option value="daily">{{ __('Daily') }}</flux:select.option>
                                    <flux:select.option value="weekly">{{ __('Weekly') }}</flux:select.option>
                                    <flux:select.option value="monthly">{{ __('Monthly') }}</flux:select.option>
                                    <flux:select.option value="yearly">{{ __('Yearly') }}</flux:select.option>
                                </flux:select>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <flux:button variant="ghost" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                        </div>
                    </form>
                @else
                    <div class="flex items-center justify-between rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <flux:checkbox wire:click="toggleComplete({{ $todo->id }})" />
                            <div class="flex-1 min-w-0 cursor-pointer" wire:click="editTodo({{ $todo->id }})">
                                <flux:heading>{{ $todo->title }}</flux:heading>
                                <div class="flex items-center gap-2 mt-1">
                                    @if ($todo->due_date)
                                        <flux:text class="text-xs">{{ $todo->due_date->format('M j, Y') }}</flux:text>
                                    @endif
                                    @if ($todo->recurrence)
                                        <flux:badge size="sm" variant="pill">{{ ucfirst($todo->recurrence) }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 ms-4">
                            <flux:button variant="ghost" size="sm" wire:click="editTodo({{ $todo->id }})" icon="pencil" />

                            <flux:modal.trigger :name="'confirm-todo-deletion-' . $todo->id">
                                <flux:button variant="ghost" size="sm" icon="trash" />
                            </flux:modal.trigger>

                            <flux:modal :name="'confirm-todo-deletion-' . $todo->id" focusable class="max-w-lg">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('Delete todo') }}</flux:heading>
                                        <flux:subheading>
                                            {{ __('Are you sure you want to delete this todo? This action cannot be undone.') }}
                                        </flux:subheading>
                                    </div>

                                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                        <flux:modal.close>
                                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>

                                        <flux:button variant="danger" wire:click="deleteTodo({{ $todo->id }})">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @if ($completedTodos->isNotEmpty())
        <div class="mt-8">
            <flux:heading size="lg" class="mb-4">{{ __('Completed') }}</flux:heading>
            <div class="space-y-4">
                @foreach ($completedTodos as $todo)
                    <div class="flex items-center justify-between rounded-xl border border-neutral-200 p-4 opacity-60 dark:border-neutral-700">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <flux:checkbox wire:click="toggleComplete({{ $todo->id }})" checked />
                            <div class="flex-1 min-w-0">
                                <flux:heading class="line-through">{{ $todo->title }}</flux:heading>
                                <div class="flex items-center gap-2 mt-1">
                                    @if ($todo->due_date)
                                        <flux:text class="text-xs">{{ $todo->due_date->format('M j, Y') }}</flux:text>
                                    @endif
                                    @if ($todo->recurrence)
                                        <flux:badge size="sm" variant="pill">{{ ucfirst($todo->recurrence) }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 ms-4">
                            <flux:modal.trigger :name="'confirm-todo-deletion-' . $todo->id">
                                <flux:button variant="ghost" size="sm" icon="trash" />
                            </flux:modal.trigger>

                            <flux:modal :name="'confirm-todo-deletion-' . $todo->id" focusable class="max-w-lg">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('Delete todo') }}</flux:heading>
                                        <flux:subheading>
                                            {{ __('Are you sure you want to delete this todo? This action cannot be undone.') }}
                                        </flux:subheading>
                                    </div>

                                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                        <flux:modal.close>
                                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>

                                        <flux:button variant="danger" wire:click="deleteTodo({{ $todo->id }})">
                                            {{ __('Delete') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
