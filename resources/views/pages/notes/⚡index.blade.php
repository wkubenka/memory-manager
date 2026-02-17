<?php

use App\Concerns\NoteValidationRules;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    use NoteValidationRules;

    #[Url]
    public string $search = '';

    public ?int $editingNoteId = null;
    public bool $creatingNote = false;
    public string $title = '';
    public string $content = '';

    /**
     * Start creating a new note.
     */
    public function startCreating(): void
    {
        $this->editingNoteId = null;
        $this->creatingNote = true;
        $this->title = '';
        $this->content = '';
    }

    /**
     * Create a new note.
     */
    public function createNote(): void
    {
        $validated = $this->validate($this->noteRules());

        Auth::user()->notes()->create($validated);

        $this->cancelEdit();
    }

    /**
     * Start editing a note.
     */
    public function editNote(int $noteId): void
    {
        $note = Auth::user()->notes()->find($noteId);

        if (! $note) {
            return;
        }

        $this->creatingNote = false;
        $this->editingNoteId = $note->id;
        $this->title = $note->title ?? '';
        $this->content = $note->content ?? '';
    }

    /**
     * Update the note being edited.
     */
    public function updateNote(): void
    {
        $validated = $this->validate($this->noteRules());

        Auth::user()->notes()->find($this->editingNoteId)?->update($validated);

        $this->cancelEdit();
    }

    /**
     * Cancel editing or creating.
     */
    public function cancelEdit(): void
    {
        $this->editingNoteId = null;
        $this->creatingNote = false;
        $this->title = '';
        $this->content = '';
        $this->resetValidation();
    }

    /**
     * Delete a note.
     */
    public function deleteNote(int $noteId): void
    {
        Auth::user()->notes()->find($noteId)?->delete();
    }

    /**
     * Toggle pin status for a note.
     */
    public function togglePin(int $noteId): void
    {
        $note = Auth::user()->notes()->find($noteId);

        if (! $note) {
            return;
        }

        if ($note->isPinned()) {
            $note->update(['pin_order' => null]);
            $this->resequencePinnedNotes();
        } else {
            $maxOrder = Auth::user()->notes()->whereNotNull('pin_order')->max('pin_order');
            $note->update(['pin_order' => ($maxOrder ?? -1) + 1]);
        }
    }

    /**
     * Handle drag-and-drop reordering of pinned notes.
     */
    public function reorderPinnedNotes(int $id, int $position): void
    {
        $note = Auth::user()->notes()->find($id);

        if (! $note || ! $note->isPinned()) {
            return;
        }

        $pinnedNotes = Auth::user()->notes()
            ->whereNotNull('pin_order')
            ->where('id', '!=', $id)
            ->orderBy('pin_order')
            ->get();

        $pinnedNotes->splice($position, 0, [$note]);

        foreach ($pinnedNotes as $index => $pinnedNote) {
            $pinnedNote->update(['pin_order' => $index]);
        }
    }

    /**
     * Resequence all pinned notes to maintain contiguous ordering.
     */
    private function resequencePinnedNotes(): void
    {
        Auth::user()->notes()
            ->whereNotNull('pin_order')
            ->orderBy('pin_order')
            ->get()
            ->each(fn (Note $note, int $index) => $note->update(['pin_order' => $index]));
    }

    /**
     * Get the user's notes.
     *
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $query = Auth::user()->notes()
            ->orderByRaw('pin_order IS NULL')
            ->orderBy('pin_order')
            ->orderBy('created_at', 'desc');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('content', 'like', '%'.$this->search.'%');
            });
        }

        $notes = $query->get();

        return [
            'pinnedNotes' => $notes->filter(fn (Note $note) => $note->isPinned())->values(),
            'unpinnedNotes' => $notes->filter(fn (Note $note) => ! $note->isPinned())->values(),
        ];
    }
}; ?>

<div class="w-full max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Notes') }}</flux:heading>
            <flux:subheading>{{ __('Manage your personal notes') }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="startCreating" icon="plus">
            {{ __('New Note') }}
        </flux:button>
    </div>

    <div class="mb-6">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search notes...')" icon="magnifying-glass" />
    </div>

    @if ($creatingNote)
        <form wire:submit="createNote" class="mb-4 rounded-xl border border-neutral-200 p-4 space-y-4 dark:border-neutral-700">
            <flux:input wire:model="title" :placeholder="__('Title')" type="text" x-init="$el.focus()" />
            <flux:textarea wire:model="content" :placeholder="__('Content')" rows="4" />
            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    @endif

    @if ($pinnedNotes->isEmpty() && $unpinnedNotes->isEmpty() && ! $creatingNote)
        <div class="rounded-xl border border-neutral-200 p-8 text-center dark:border-neutral-700">
            <flux:heading>{{ __('No notes yet') }}</flux:heading>
            <flux:subheading>{{ __('Create your first note to get started.') }}</flux:subheading>

            <div class="mt-4">
                <flux:button variant="primary" wire:click="startCreating">
                    {{ __('Create a Note') }}
                </flux:button>
            </div>
        </div>
    @endif

    @if ($pinnedNotes->isNotEmpty())
        <div class="space-y-4" wire:sort="reorderPinnedNotes">
            @foreach ($pinnedNotes as $note)
                <div wire:key="{{ $note->id }}" wire:sort:item="{{ $note->id }}">
                    @if ($editingNoteId === $note->id)
                        <form wire:submit="updateNote" class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 space-y-4 dark:border-neutral-700 dark:bg-neutral-800/50">
                            <flux:input wire:model="title" :placeholder="__('Title')" type="text" x-init="$el.focus()" />
                            <flux:textarea wire:model="content" :placeholder="__('Content')" rows="4" />
                            <div class="flex items-center justify-between">
                                <div>
                                    <flux:modal.trigger :name="'confirm-note-deletion-' . $note->id">
                                        <flux:button variant="ghost" size="sm" icon="trash" data-test="delete-note-button" />
                                    </flux:modal.trigger>

                                    <flux:modal :name="'confirm-note-deletion-' . $note->id" focusable class="max-w-lg">
                                        <div class="space-y-6">
                                            <div>
                                                <flux:heading size="lg">{{ __('Delete note') }}</flux:heading>
                                                <flux:subheading>
                                                    {{ __('Are you sure you want to delete this note? This action cannot be undone.') }}
                                                </flux:subheading>
                                            </div>

                                            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                                <flux:modal.close>
                                                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                                </flux:modal.close>

                                                <flux:button variant="danger" wire:click="deleteNote({{ $note->id }})" data-test="confirm-delete-note-button">
                                                    {{ __('Delete') }}
                                                </flux:button>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </div>

                                <div class="flex items-center gap-2">
                                    <flux:button variant="ghost" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                                    <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-800/50">
                            <div wire:sort:handle class="flex items-center pt-0.5 cursor-grab text-neutral-400">
                                <flux:icon name="bars-2" variant="mini" />
                            </div>

                            <div class="flex-1 min-w-0 cursor-pointer" wire:click="editNote({{ $note->id }})">
                                <flux:heading>
                                    {{ $note->title ?: __('Untitled') }}
                                </flux:heading>
                                @if ($note->content)
                                    <flux:subheading class="mt-1">{!! nl2br(e($note->content)) !!}</flux:subheading>
                                @endif
                                <flux:text class="mt-2 text-xs">
                                    {{ $note->updated_at->diffForHumans() }}
                                </flux:text>
                            </div>

                            <div class="flex items-center ms-4" wire:sort:ignore>
                                <flux:button variant="ghost" size="sm" wire:click="togglePin({{ $note->id }})" icon="pin-off" />
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($unpinnedNotes->isNotEmpty())
        <div class="space-y-4 {{ $pinnedNotes->isNotEmpty() ? 'mt-4' : '' }}">
            @foreach ($unpinnedNotes as $note)
                @if ($editingNoteId === $note->id)
                    <form wire:key="{{ $note->id }}" wire:submit="updateNote" class="rounded-xl border border-neutral-200 p-4 space-y-4 dark:border-neutral-700">
                        <flux:input wire:model="title" :placeholder="__('Title')" type="text" x-init="$el.focus()" />
                        <flux:textarea wire:model="content" :placeholder="__('Content')" rows="4" />
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:modal.trigger :name="'confirm-note-deletion-' . $note->id">
                                    <flux:button variant="ghost" size="sm" icon="trash" data-test="delete-note-button" />
                                </flux:modal.trigger>

                                <flux:modal :name="'confirm-note-deletion-' . $note->id" focusable class="max-w-lg">
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">{{ __('Delete note') }}</flux:heading>
                                            <flux:subheading>
                                                {{ __('Are you sure you want to delete this note? This action cannot be undone.') }}
                                            </flux:subheading>
                                        </div>

                                        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                            <flux:modal.close>
                                                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                            </flux:modal.close>

                                            <flux:button variant="danger" wire:click="deleteNote({{ $note->id }})" data-test="confirm-delete-note-button">
                                                {{ __('Delete') }}
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            </div>

                            <div class="flex items-center gap-2">
                                <flux:button variant="ghost" wire:click="cancelEdit">{{ __('Cancel') }}</flux:button>
                                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                            </div>
                        </div>
                    </form>
                @else
                    <div wire:key="{{ $note->id }}" class="flex items-start justify-between rounded-xl border border-neutral-200 p-4 ps-12 dark:border-neutral-700">
                        <div class="flex-1 min-w-0 cursor-pointer" wire:click="editNote({{ $note->id }})">
                            <flux:heading>
                                {{ $note->title ?: __('Untitled') }}
                            </flux:heading>
                            @if ($note->content)
                                <flux:subheading class="mt-1">{!! nl2br(e($note->content)) !!}</flux:subheading>
                            @endif
                            <flux:text class="mt-2 text-xs">
                                {{ $note->updated_at->diffForHumans() }}
                            </flux:text>
                        </div>

                        <div class="flex items-center ms-4">
                            <flux:button variant="ghost" size="sm" wire:click="togglePin({{ $note->id }})" icon="pin" />
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>
