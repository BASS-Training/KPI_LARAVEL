<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sla extends Model
{
    protected $table = 'sla';

    protected $fillable = [
        'nama_pekerjaan',
        'jabatan',
        'durasi_jam',
        'keterangan',
    ];
}
