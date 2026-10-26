# Shared prompt and question modals

Import from `@/Components/Aadi/Dialogs`. These components work in any Vue layout;
no global host or plugin is required. Native modal dialogs are teleported to the
body, trap keyboard focus, label their content, and restore the opener's focus.
Escape cancels when idle. Clicking the backdrop does not dismiss a question.

## Confirmation / question

```vue
<QuestionModal
    :open="showDelete"
    title="Delete this listing?"
    description="This action cannot be undone here."
    confirm-label="Delete listing"
    cancel-label="Keep listing"
    busy-label="Deleting…"
    danger
    :busy="form.processing"
    :error="deleteError"
    @confirm="removeListing"
    @cancel="showDelete = false"
/>
```

The default slot accepts additional form fields or explanatory content. Do not
nest another form: QuestionModal owns the form and submit action. Use
`data-dialog-focus` on a slotted field to focus it on open; otherwise Cancel gets
initial focus. Use one active question/prompt at a time.

## Text response / prompt

```vue
<PromptModal
    v-if="reviewing"
    :open="true"
    v-model="form.reviewNote"
    title="Request an application update"
    label="Review note"
    hint="Explain which documents need updating."
    required
    multiline
    :max-length="2000"
    :busy="form.processing"
    :error="form.errors.reviewNote"
    confirm-label="Send for update"
    @confirm="submitReview"
    @cancel="reviewing = false"
/>
```

Omit `multiline` for a single-line input. Omit `required` for optional responses.
Both components accept title, description, confirmLabel, cancelLabel, busyLabel,
busy, danger and error. PromptModal additionally accepts modelValue, label, hint,
required, multiline and maxLength (default 2000).

The caller owns visibility, input, validation errors, and requests. Reset data and
errors before opening; close only after a successful request. Keep the modal open
on errors so users can correct and retry. Bind busy to request state to disable
submit/cancel and Escape while saving, and guard duplicate calls in the handler.
Server validation and authorization remain required.

Current integrations: marketplace listing deletion (QuestionModal) and admin
provider application decisions (PromptModal). The existing generic Modal remains
available for unrelated custom dialogs.
