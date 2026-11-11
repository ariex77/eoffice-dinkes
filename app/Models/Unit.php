<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['parent_id', 'kode_nomor', 'nama', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function label(): string
    {
        return $this->kode_nomor ? "{$this->kode_nomor} — {$this->nama}" : $this->nama;
    }

    /** ID unit ini beserta seluruh turunannya. */
    public function descendantIds(): array
    {
        $byParent = static::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [$this->id];
        $queue = [$this->id];

        while ($queue) {
            $current = array_shift($queue);
            foreach ($byParent->get($current, []) as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }
}
