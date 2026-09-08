<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One choice belonging to a field.
 *
 * `label` is read by the visitor, `value` is what a response stores. Keeping
 * them apart is what lets an admin reword "Python Full Stack" without rewriting
 * every answer already filed under it.
 *
 * `group` says which list the row belongs to. A dropdown has plain options; a
 * grid has rows down the side and columns across the top, and both are managed
 * with the same editor and the same code because they are the same shape.
 */
class FormFieldOption extends Model
{
    public const OPTION = 'option';
    public const ROW    = 'row';
    public const COLUMN = 'column';

    public const GROUPS = [self::OPTION, self::ROW, self::COLUMN];

    protected $fillable = ['form_field_id', 'group', 'label', 'value', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    protected $attributes = ['group' => self::OPTION];

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }
}
