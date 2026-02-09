<?php

namespace App\Concerns;

trait NoteValidationRules
{
    /**
     * Get the validation rules used to validate notes.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function noteRules(): array
    {
        return [
            'title' => $this->titleRules(),
            'content' => $this->contentRules(),
        ];
    }

    /**
     * Get the validation rules used to validate note titles.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function titleRules(): array
    {
        return ['nullable', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate note content.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function contentRules(): array
    {
        return ['nullable', 'string'];
    }
}
