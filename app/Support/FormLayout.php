<?php

namespace App\Support;

use App\Models\Form;
use Illuminate\Support\Collection;

/**
 * Arranges a form's questions into the shape the form says it is.
 *
 * One function serves all four structures, and everything that renders a form —
 * the public page, the admin preview — walks its output rather than branching on
 * the structure itself. Whatever the shape, the result is the same nesting:
 *
 *     pages[] → sections[] → fields
 *
 * A plain form is one unnamed page holding one unnamed section. That is not a
 * fudge to make the loop work; it is what a plain form IS, and it means the
 * renderer has no "if this is a plain form" branch to get wrong.
 *
 * THE RULE THIS CLASS EXISTS TO KEEP: every live question comes out somewhere.
 *
 * The two columns that place a question are nullable and are cleared rather than
 * cascaded when a page or section is deleted, so questions genuinely do come
 * loose — a page deleted straight from the database, a form switched from pages
 * to plain and back, a form built before this module had pages at all. A
 * renderer that trusted the ids would silently stop drawing those questions, and
 * a question that is not drawn is one nobody can answer. So anything unplaced is
 * collected into an unnamed bucket at the front of the first page: visible,
 * answerable, and wearing no heading it was never given.
 */
class FormLayout
{
    /**
     * @return array<int, array{
     *     id: ?int, title: ?string, description: ?string, heading: string,
     *     sections: array<int, array{id: ?int, title: ?string, description: ?string, fields: Collection}>
     * }>
     */
    public static function for(Form $form): array
    {
        $fields = $form->fields;

        return match ($form->structure()) {
            Form::SECTIONS       => self::onePage(self::sectionise($form, $fields, null)),
            Form::PAGES          => self::paged($form, $fields, false),
            Form::PAGES_SECTIONS => self::paged($form, $fields, true),
            default              => self::onePage([self::section(null, $fields)]),
        };
    }

    /** How many steps a visitor will be walked through. 1 for every non-paged form. */
    public static function stepCount(Form $form): int
    {
        return $form->hasPages() ? max(1, count(self::for($form))) : 1;
    }

    /* =============================== PAGES ================================= */

    private static function paged(Form $form, Collection $fields, bool $withSections): array
    {
        $pages = $form->pages;

        // A form marked multi-page with no pages on it. Rather than render an
        // empty screen, fall back to the single-page shape — the questions are
        // the point, and they are all still here.
        if ($pages->isEmpty()) {
            return self::onePage(
                $withSections ? self::sectionise($form, $fields, null) : [self::section(null, $fields)],
            );
        }

        $ids   = $pages->pluck('id')->all();
        $owned = fn ($field) => $field->form_page_id !== null && in_array($field->form_page_id, $ids, true);

        // Questions belonging to no page of this form, kept for the first page.
        $loose = $fields->reject($owned)->values();

        $out = [];

        foreach ($pages->values() as $i => $page) {
            $mine = $fields->filter(fn ($f) => $f->form_page_id === $page->id)->values();

            // Everything unplaced joins the first page, ahead of its own
            // questions — these are almost always questions that predate the
            // pages, and they read as the form's opening.
            if ($i === 0 && $loose->isNotEmpty()) {
                $mine = $loose->concat($mine)->values();
            }

            $out[] = [
                'id'          => $page->id,
                'title'       => $page->title,
                'description' => $page->description,
                'heading'     => $page->heading($i + 1),
                'sections'    => $withSections
                    ? self::sectionise($form, $mine, $page->id)
                    : [self::section(null, $mine)],
            ];
        }

        return $out;
    }

    /* ============================== SECTIONS =============================== */

    /**
     * Split one page's questions across that page's sections.
     *
     * $pageId is null for a single-page form, where the sections hang off the
     * form itself.
     */
    private static function sectionise(Form $form, Collection $fields, ?int $pageId): array
    {
        $sections = $form->sections->filter(fn ($s) => $s->form_page_id === $pageId)->values();

        if ($sections->isEmpty()) {
            return [self::section(null, $fields)];
        }

        $ids   = $sections->pluck('id')->all();
        $loose = $fields->reject(
            fn ($f) => $f->form_section_id !== null && in_array($f->form_section_id, $ids, true),
        )->values();

        $out = [];

        // Unplaced questions first, under no heading at all.
        if ($loose->isNotEmpty()) {
            $out[] = self::section(null, $loose);
        }

        foreach ($sections as $i => $section) {
            $out[] = [
                'id'          => $section->id,
                'title'       => $section->title,
                'description' => $section->description,
                'heading'     => $section->heading($i + 1),
                'fields'      => $fields->filter(fn ($f) => $f->form_section_id === $section->id)->values(),
            ];
        }

        // A section with no questions in it is a heading over nothing. The
        // builder allows one — it is a section the admin has not filled in yet —
        // but the public page should not draw it.
        return array_values(array_filter($out, fn ($s) => $s['fields']->isNotEmpty()));
    }

    /* =============================== SHAPES ================================ */

    private static function onePage(array $sections): array
    {
        return [[
            'id'          => null,
            'title'       => null,
            'description' => null,
            'heading'     => 'Page 1',
            'sections'    => $sections,
        ]];
    }

    private static function section(?string $title, Collection $fields): array
    {
        return [
            'id'          => null,
            'title'       => $title,
            'description' => null,
            'heading'     => (string) $title,
            'fields'      => $fields,
        ];
    }
}
