<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_setting_id',
        'parent_category_id',
        'name',
        'slug',
        'description',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_category_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_category_id');
    }

    /**
     * Recursively deactivate all descendant categories.
     */
    public function deactivateDescendants(): void
    {
        $children = $this->children()->get();

        foreach ($children as $child) {
            // Force update to false regardless of current state
            $child->update(['is_active' => false]);

            // Recurse into grandchildren
            $child->deactivateDescendants();
        }
    }
}
