{{-- Search-and-add picker: type to filter, click a result to attach it as a tag.
     Each tag carries a hidden `{$name}[]` input, so the form posts a plain array
     of ids and the controller decides what attaching/detaching means.

     @param string $key       unique per page — used for element ids
     @param string $name      form field name (posted as "{$name}[]")
     @param iterable $items   rows of ['id', 'name', 'icon' (url|null), 'owner' (label|'')]
     @param array  $selected  ids to start with
     @param string $placeholder
     @param string $hint      helper line under the picker (optional) --}}

@php
    $items    = collect($items)->values();
    $selected = collect($selected)->map(fn ($id) => (int) $id)->values();
@endphp

<div class="hm-picker" id="{{ $key }}Picker">
    <input type="text" id="{{ $key }}Search" class="form-control-hm"
           placeholder="{{ $placeholder ?? 'Search…' }}" autocomplete="off" role="combobox"
           aria-expanded="false" aria-controls="{{ $key }}Menu" aria-autocomplete="list">
    <div class="hm-picker__menu" id="{{ $key }}Menu" role="listbox"></div>
</div>
{{-- Outside .hm-picker: that box shrink-wraps the input, and a long tag list
     living inside it would stretch the dropdown with it. --}}
<div class="hm-picker__tags" id="{{ $key }}Tags"></div>

@if (! empty($hint))
    <p class="form-hint">{{ $hint }}</p>
@endif

@push('scripts')
<script>
(function () {
    var root = document.getElementById('{{ $key }}Picker');
    if (!root) return;

    var ALL      = @json($items),
        selected = @json($selected).map(Number),
        FIELD    = @json($name),
        search   = document.getElementById('{{ $key }}Search'),
        menu     = document.getElementById('{{ $key }}Menu'),
        tags     = document.getElementById('{{ $key }}Tags'),
        active   = -1;

    function available() {
        var q = search.value.trim().toLowerCase();
        return ALL.filter(function (c) {
            return selected.indexOf(c.id) === -1 && (!q || c.name.toLowerCase().indexOf(q) !== -1);
        });
    }

    function renderMenu() {
        var list = available();
        active = list.length ? 0 : -1;
        menu.innerHTML = '';

        if (!list.length) {
            var empty = document.createElement('div');
            empty.className = 'hm-picker__empty';
            empty.textContent = search.value.trim() ? 'Nothing matches that search.' : 'Everything has been added already.';
            menu.appendChild(empty);
            return;
        }

        list.forEach(function (c, i) {
            var opt = document.createElement('button');
            opt.type = 'button';
            opt.className = 'hm-picker__opt' + (i === 0 ? ' is-active' : '');
            opt.setAttribute('role', 'option');
            opt.dataset.id = c.id;

            var main = document.createElement('span');
            main.className = 'hm-picker__opt-main';

            // The row's own image when it has one, otherwise a neutral tag glyph.
            var ico = document.createElement('span');
            ico.className = 'hm-picker__ico';
            if (c.icon) {
                var img = document.createElement('img');
                img.src = c.icon;
                img.alt = '';
                ico.appendChild(img);
            } else {
                ico.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.2" fill="currentColor"/></svg>';
            }
            main.appendChild(ico);

            var name = document.createElement('span');
            name.className = 'hm-picker__opt-name';
            name.textContent = c.name;
            main.appendChild(name);

            opt.appendChild(main);

            if (c.owner) {
                var meta = document.createElement('span');
                meta.className = 'hm-picker__opt-meta';
                meta.textContent = c.owner;
                opt.appendChild(meta);
            }

            opt.addEventListener('mouseenter', function () { setActive(i); });
            opt.addEventListener('click', function () { add(c.id); });
            menu.appendChild(opt);
        });
    }

    function setActive(i) {
        var opts = menu.querySelectorAll('.hm-picker__opt');
        if (!opts.length) return;
        active = (i + opts.length) % opts.length;
        opts.forEach(function (o, n) { o.classList.toggle('is-active', n === active); });
        opts[active].scrollIntoView({ block: 'nearest' });
    }

    function renderTags() {
        tags.innerHTML = '';
        selected.forEach(function (id) {
            var row = ALL.filter(function (c) { return c.id === id; })[0];
            if (!row) return;

            var tag = document.createElement('span');
            tag.className = 'hm-tag';
            tag.appendChild(document.createTextNode(row.name));

            var x = document.createElement('button');
            x.type = 'button';
            x.className = 'hm-tag__x';
            x.setAttribute('aria-label', 'Remove ' + row.name);
            x.innerHTML = '&times;';
            x.addEventListener('click', function () { remove(id); });
            tag.appendChild(x);

            var field = document.createElement('input');
            field.type = 'hidden';
            field.name = FIELD + '[]';
            field.value = id;
            tag.appendChild(field);

            tags.appendChild(tag);
        });
    }

    function add(id) {
        if (selected.indexOf(id) === -1) selected.push(id);
        search.value = '';
        renderTags();
        renderMenu();
        search.focus();
    }

    function remove(id) {
        selected = selected.filter(function (s) { return s !== id; });
        renderTags();
        if (root.classList.contains('is-open')) renderMenu();
    }

    function open()  { renderMenu(); root.classList.add('is-open'); search.setAttribute('aria-expanded', 'true'); }
    function close() { root.classList.remove('is-open');            search.setAttribute('aria-expanded', 'false'); }

    search.addEventListener('focus', open);
    search.addEventListener('input', function () { renderMenu(); root.classList.add('is-open'); });

    search.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!root.classList.contains('is-open')) return open();
            setActive(active + (e.key === 'ArrowDown' ? 1 : -1));
        } else if (e.key === 'Enter') {
            // Never let the picker submit the form — Enter here means "add".
            e.preventDefault();
            var opt = menu.querySelectorAll('.hm-picker__opt')[active];
            if (opt) add(Number(opt.dataset.id));
        } else if (e.key === 'Escape') {
            close();
        }
    });

    document.addEventListener('click', function (e) { if (!root.contains(e.target)) close(); });

    renderTags();
})();
</script>
@endpush
