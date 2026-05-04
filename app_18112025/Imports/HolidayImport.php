<?php

namespace App\Imports;

use App\Models\Holiday;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HolidayImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Holiday([
            'hol_name'        => $row['hol_name'],
            'hol_date'        => Carbon::parse($row['hol_date'])->format('Y-m-d'),
            'hol_year'        => $row['hol_year'],
            'hol_description' => $row['hol_description'],
            'hol_is_active'   => $row['hol_is_active'],
        ]);
    }
}
