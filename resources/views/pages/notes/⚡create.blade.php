<?php

use App\Concerns\NoteValidationRules;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use NoteValidationRules;

    public string $title = '';
    public string $content = '';

    /**
     * Create a new note.
     */
    public function createNote(): void
    {
        $validated = $this->validate($this->noteRules());

        Auth::user()->notes()->create($validated);

        $this->redirect(route('notes.index'), navigate: true);
    }
}; ?>

<div class="w-full max-w-2xl mx-auto">
    <flux:heading size="xl" class="mb-6">{{ __('Create Note') }}</flux:heading>

    <form wire:submit="createNote" class="space-y-6">
        <flux:input wire:model="title" :label="__('Title')" type="text" autofocus />

        <flux:textarea wire:model="content" :label="__('Content')" rows="8" />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit" data-test="create-note-button">
                {{ __('Create Note') }}
            </flux:button>

            <flux:button variant="ghost" :href="route('notes.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</div>
