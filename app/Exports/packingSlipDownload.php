<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class packingSlipDownload implements FromCollection, WithHeadings
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return [
            'Institute Code',
            'Subject Name',
            'Semester Code',
            'Packet Count',
            'Packet Required',
            'Q Code',
            'Subject D Code',
            'Exam Date',
            'Exam Half',
        ];
    }
}
