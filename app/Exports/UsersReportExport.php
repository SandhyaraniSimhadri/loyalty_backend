<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class UsersReportExport implements FromCollection, WithHeadings
{
    protected $rows;

    public function __construct($data)
    {
        $this->rows = $data;
    }

    public function collection()
    {
        $formattedData = [];
        $i = 0;
        // return $this->rows;
        foreach ($this->rows as $row) {
                    $i=$i+1;
                 
                    $formattedData[] = [
                        'S.No' => $i, 
                        'User Id' => $row['id'],
                        'Company Id' => $row['company_id'],
                        'Company Name' => $row['company_name'],
                        'User Name' => $row['user_name'],
                        'Email' => $row['email'],
                        'Mobile Number' => $row['mobile_no'],
                        'City' => $row['city']

                    ];
                  
                }

        // Return a Collection instance
        return new Collection($formattedData);
    }

    public function headings(): array
    {
        return [
            'S.No',
            'User Id',
            'Company Id',
            'Company Name',
            'User Name',
            'Email',
            'Mobile Number',
            'City'
        ];
    }
}
