<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sla extends Model
{
    protected $table = 'sla';

    protected $fillable = [
        'nama_pekerjaan',
        'jabatan',
        'position_id',
        'durasi_jam',
        'keterangan',
    ];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
