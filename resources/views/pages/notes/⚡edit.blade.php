<?php

use App\Concerns\NoteValidationRules;
use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use NoteValidationRules;

    public Note $note;
    public string $title = '';
    public string $content = '';

    /**
     * Mount the component.
     */
    public function mount(Note $note): void
    {
        if ($note->user_id !== Auth::id()) {
            abort(403);
        }

        $this->note = $note;
        $this->title = $note->title ?? '';
        $this->content = $note->content ?? '';
    }

    /**
     * Update the note.
     */
    public function updateNote(): void
    {
        $validated = $this->validate($this->noteRules());

        $this->note->update($validated);

        $this->dispatch('note-updated');
    }
}; ?>

<div class="w-full max-w-2xl mx-auto">
    <flux:heading size="xl" class="mb-6">{{ __('Edit Note') }}</flux:heading>

    <form wire:submit="updateNote" class="space-y-6">
        <flux:input wire:model="title" :label="__('Title')" type="text" autofocus />

        <flux:textarea wire:model="content" :label="__('Content')" rows="8" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit" data-test="update-note-button">
                {{ __('Save') }}
            </flux:button>

            <x-action-message class="me-3" on="note-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</div>
