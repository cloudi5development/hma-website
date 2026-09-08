{{--
| One choice under a field — a plain option, or a grid's row or column.
|
| $r     the field's row key, $o the choice's key within its list. Both opaque;
|        display order is position in the DOM, as with the fields themselves.
| $list  which list this belongs to: 'options' (default), 'rows' or 'columns'.
|        It is the name segment, so one partial serves all three managers and
|        one set of handlers drives them.
|
| The stored value is separate from the label so the wording can change later
| without rewriting every answer already filed under it. Left blank it falls
| back to the label, which is what an admin who never opens the value box
| expects to see in their export.
|
| The controls use .fb-opt-btn rather than the panel's .btn-icon: that class is
| 40px square and sizes only .act-ico spans, so a raw <svg> inside one comes out
| at its intrinsic size. These rows need something smaller anyway.
--}}
@php $list ??= 'options'; @endphp

<div class="fb-option" data-option data-list="{{ $list }}" draggable="false">
    <span class="fb-option__grip" data-option-grip title="Drag to reorder" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/></svg>
    </span>

    <input type="text" name="fields[{{ $r }}][{{ $list }}][{{ $o }}][label]"
           class="form-control-hm fb-option__input" value="{{ $option['label'] ?? '' }}"
           placeholder="{{ $list === 'rows' ? 'Row label' : ($list === 'columns' ? 'Column label' : 'Option label — what people see') }}"
           data-option-label>

    <input type="text" name="fields[{{ $r }}][{{ $list }}][{{ $o }}][value]"
           class="form-control-hm fb-option__input fb-option__input--value" value="{{ $option['value'] ?? '' }}"
           placeholder="Stored value (optional)">

    {{-- Remove only. Reordering is the grip on the left, which drags the row
         within its own list — see the builder script. --}}
    <button type="button" class="fb-opt-btn fb-opt-btn--remove" data-option-remove aria-label="Remove" title="Remove">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </button>
</div>
