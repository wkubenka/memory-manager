<?php

use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url]
    public string $search = '';

    /**
     * Delete a note.
     */
    public function deleteNote(int $noteId): void
    {
        Auth::user()->notes()->find($noteId)?->delete();
    }

    /**
     * Get the user's notes.
     *
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $query = Auth::user()->notes()->latest();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('content', 'like', '%'.$this->search.'%');
            });
        }

        return [
            'notes' => $query->get(),
        ];
    }
}; ?>

<div class="w-full max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <flux:heading size="xl">{{ __('Notes') }}</flux:heading>
            <flux:subheading>{{ __('Manage your personal notes') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('notes.create')" wire:navigate icon="plus">
            {{ __('New Note') }}
        </flux:button>
    </div>

    <div class="mb-6">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search notes...')" icon="magnifying-glass" />
    </div>

    @if ($notes->isEmpty())
        <div class="rounded-xl border border-neutral-200 p-8 text-center dark:border-neutral-700">
            <flux:heading>{{ __('No notes yet') }}</flux:heading>
            <flux:subheading>{{ __('Create your first note to get started.') }}</flux:subheading>

            <div class="mt-4">
                <flux:button variant="primary" :href="route('notes.create')" wire:navigate>
                    {{ __('Create a Note') }}
                </flux:button>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($notes as $note)
                <div class="flex items-start justify-between rounded-xl border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="flex-1 min-w-0">
                        <flux:heading>
                            <flux:link :href="route('notes.edit', $note)" wire:navigate>
                                {{ $note->title ?: __('Untitled') }}
                            </flux:link>
                        </flux:heading>
                        @if ($note->content)
                            <flux:subheading class="mt-1 truncate">
                                {{ Str::limit($note->content, 100) }}
                            </flux:subheading>
                        @endif
                        <flux:text class="mt-2 text-xs">
                            {{ $note->updated_at->diffForHumans() }}
                        </flux:text>
                    </div>

                    <div class="flex items-center gap-2 ms-4">
                        <flux:button variant="ghost" size="sm" :href="route('notes.edit', $note)" wire:navigate icon="pencil" />

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
                </div>
            @endforeach
        </div>
    @endif
</div>
