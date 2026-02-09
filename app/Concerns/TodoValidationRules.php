<?php

namespace App\Concerns;

trait TodoValidationRules
{
    /**
     * Get the validation rules used to validate todos.
     *
     * @return array<string, array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>>
     */
    protected function todoRules(): array
    {
        return [
            'title' => $this->titleRules(),
            'dueDate' => $this->dueDateRules(),
            'recurrence' => $this->recurrenceRules(),
        ];
    }

    /**
     * Get the validation rules used to validate todo titles.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function titleRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate todo due dates.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function dueDateRules(): array
    {
        return ['nullable', 'date'];
    }

    /**
     * Get the validation rules used to validate todo recurrence.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function recurrenceRules(): array
    {
        return ['nullable', 'string', 'in:daily,weekly,monthly,yearly'];
    }
}
