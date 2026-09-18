<?php

namespace App\Support;

use App\Models\Form;
use App\Models\FormField;

/**
 * The nesting the BUILDER screen edits: pages → sections → question rows.
 *
 * FormLayout does the same job for the public page, and the two are deliberately
 * not the same class. A renderer wants the form as it will be read — empty
 * sections dropped, nothing shown that has nothing in it. A builder wants the
 * form as it is being WORKED ON — the empty section the admin just added and has
 * not filled in yet is the most important thing on their screen.
 *
 * THE ONE IDEA THAT MAKES THE FOUR STRUCTURES ONE BUILDER:
 *
 * the page → section → questions nesting is ALWAYS in the DOM, whatever shape
 * the form is. A plain form is one page holding one section, with the chrome of
 * both hidden. Choosing a structure only decides which headings the admin can
 * see and edit; it never moves a question.
 *
 * That is what makes switching structures safe. The browser posts the whole
 * nesting every time, and FormBuilderService keeps only the parts the chosen
 * structure allows — so a multi-page form switched to plain simply has its
 * page_refs ignored, and every question lands on one page in the order it was
 * already in. No question is moved, none is lost, and there is no migration
 * step between the four shapes.
 */
class FormBuilderTree
{
    /** The key an unsaved container is posted under. Ids take over once saved. */
    private const NEW_PAGE    = 'p0';

    private const NEW_SECTION = 's0';

    /**
     * @return array<int, array{ref: string, id: ?int, title: string, description: string,
     *     sections: array<int, array{ref: string, id: ?int, title: string, description: string,
     *     fields: array<string, array>}>}>
     */
    public static function for(Form $form): array
    {
        // A failed save gets the admin their work back rather than throwing away
        // a form they spent ten minutes on — including the pages and sections,
        // which is why all three lists are read from old() together or not at
        // all. Half from the browser and half from the database would put
        // questions into sections that no longer matched.
        return old('fields') === null && old('pages') === null && old('sections') === null
            ? self::fromDatabase($form)
            : self::fromOldInput();
    }

    /* ============================== DATABASE =============================== */

    private static function fromDatabase(Form $form): array
    {
        $fields = $form->exists
            ? $form->fields()->with(['options', 'rows', 'columns'])->get()
            : collect();

        $pages    = $form->exists ? $form->pages : collect();
        $sections = $form->exists ? $form->sections : collect();

        // One page always exists on screen even when the form has none saved:
        // it is the page a plain form's questions are already sitting in, ready
        // to be given a name the moment the admin picks a multi-page structure.
        $pageRows = $pages->isEmpty()
            ? [self::page(self::NEW_PAGE, null, '', '')]
            : $pages->map(fn ($p) => self::page('p' . $p->id, $p->id, $p->title, $p->description))->all();

        $pageIds = $pages->pluck('id')->all();

        foreach ($pageRows as $i => &$page) {
            $mine = $pages->isEmpty()
                ? $fields
                : $fields->filter(fn ($f) => $f->form_page_id === $page['id']);

            // Questions belonging to no page of this form join the first one, so
            // that a question can never be edited out of existence by sitting in
            // a container that is no longer there. Same rule as FormLayout.
            if ($i === 0) {
                $mine = $fields
                    ->reject(fn ($f) => $f->form_page_id !== null && in_array($f->form_page_id, $pageIds, true))
                    ->concat($mine)
                    ->unique('id');
            }

            $page['sections'] = self::sectionsFor($sections, $page['id'], $mine, self::newSectionRef($i, $page['ref']));
        }

        return $pageRows;
    }

    /**
     * The ref for the unsaved section a page is given when it has none — which
     * is every page of a Multi-Page form, since that shape keeps no sections.
     *
     * One per PAGE. They all used to be "s0", so the moment such a form was
     * switched to Multi-Page + Sections the posted sections[s0] of each page
     * overwrote the last, and every question on every page was saved into the
     * one section that survived — on the last page.
     */
    private static function newSectionRef(int $pageIndex, string $pageRef): string
    {
        return $pageIndex === 0 ? self::NEW_SECTION : self::NEW_SECTION . $pageRef;
    }

    /** One page's sections, each carrying its questions. */
    private static function sectionsFor($sections, ?int $pageId, $fields, string $newRef = self::NEW_SECTION): array
    {
        $mine = $sections->filter(fn ($s) => $s->form_page_id === $pageId)->values();

        if ($mine->isEmpty()) {
            return [self::section($newRef, null, '', '', self::rows($fields))];
        }

        $ids  = $mine->pluck('id')->all();
        $rows = [];

        foreach ($mine as $i => $section) {
            $held = $fields->filter(fn ($f) => $f->form_section_id === $section->id);

            if ($i === 0) {
                $held = $fields
                    ->reject(fn ($f) => $f->form_section_id !== null && in_array($f->form_section_id, $ids, true))
                    ->concat($held)
                    ->unique('id');
            }

            $rows[] = self::section(
                's' . $section->id, $section->id, $section->title, $section->description, self::rows($held),
            );
        }

        return $rows;
    }

    /** Flatten field models into the plain arrays the row partial reads. */
    private static function rows($fields): array
    {
        return collect($fields)->mapWithKeys(fn (FormField $field) => ['f' . $field->id => array_merge(
            $field->only(['id', 'field_type', 'label', 'field_key', 'placeholder', 'help_text', 'is_required', 'default_value']),
            (array) $field->validation_rules,
            [
                'multiple'       => $field->setting('multiple'),
                'cond_field_key' => $field->condition()['field_key'] ?? '',
                'cond_operator'  => $field->condition()['operator'] ?? 'equals',
                'cond_value'     => $field->condition()['value'] ?? '',
                'options'        => $field->options->map->only(['label', 'value'])->all(),
                // A grid's two lists. Empty for every other type, and the panels
                // holding them are hidden then anyway.
                'rows'           => $field->rows->map->only(['label', 'value'])->all(),
                'columns'        => $field->columns->map->only(['label', 'value'])->all(),
                'correct_answer' => $field->correctAnswer(),
            ],
        )])->all();
    }

    /* ============================== OLD INPUT ==============================
       Rebuilt from the refs the browser posted rather than from ids, because
       the containers the admin had just added have no ids yet — and those are
       exactly the ones a failed save must not throw away. */

    private static function fromOldInput(): array
    {
        $pages    = (array) old('pages', []);
        $sections = (array) old('sections', []);
        $fields   = (array) old('fields', []);

        if ($pages === []) {
            $pages = [self::NEW_PAGE => []];
        }

        $out   = [];
        $index = 0;

        foreach ($pages as $ref => $page) {
            $ref  = (string) $ref;
            $mine = array_filter($sections, fn ($s) => (string) ($s['page_ref'] ?? self::NEW_PAGE) === $ref);

            if ($mine === []) {
                $mine = [self::newSectionRef($index, $ref) => []];
            }

            $index++;

            $rows = [];

            foreach ($mine as $sectionRef => $section) {
                $sectionRef = (string) $sectionRef;

                $rows[] = self::section(
                    $sectionRef,
                    isset($section['id']) && $section['id'] !== '' ? (int) $section['id'] : null,
                    (string) ($section['title'] ?? ''),
                    (string) ($section['description'] ?? ''),
                    array_filter(
                        $fields,
                        fn ($f) => (string) ($f['section_ref'] ?? self::NEW_SECTION) === $sectionRef,
                    ),
                );
            }

            // Assigned, not added with `+`: page() already carries an empty
            // 'sections', and an array union keeps the LEFT side's key — so the
            // sections (and every question in them) were silently dropped, and
            // a failed save redrew the builder empty.
            $built             = self::page(
                $ref,
                isset($page['id']) && $page['id'] !== '' ? (int) $page['id'] : null,
                (string) ($page['title'] ?? ''),
                (string) ($page['description'] ?? ''),
            );
            $built['sections'] = $rows;
            $out[]             = $built;
        }

        return $out;
    }

    /* =============================== SHAPES ================================ */

    private static function page(string $ref, ?int $id, ?string $title, ?string $description): array
    {
        return [
            'ref'         => $ref,
            'id'          => $id,
            'title'       => (string) $title,
            'description' => (string) $description,
            'sections'    => [],
        ];
    }

    private static function section(string $ref, ?int $id, ?string $title, ?string $description, array $fields): array
    {
        return [
            'ref'         => $ref,
            'id'          => $id,
            'title'       => (string) $title,
            'description' => (string) $description,
            'fields'      => $fields,
        ];
    }
}
