<?php

namespace App\Service;

class FileStorage
{
    public const FILENAME = 'exports.json';

    public static function read(): array
    {
        if (!file_exists(self::FILENAME)) {
            return [];
        }

        $data = file_get_contents(self::FILENAME);

        return \json_decode($data, true);
    }

    private static function write(array $data)
    {
        $data = \json_encode($data);

        $result = file_put_contents(self::FILENAME, $data);

        if ($result === false) {
            throw new \Exception('Cant save file');
        }
    }

    public static function getById(string $id)
    {
        $data = self::read();

        foreach ($data as $row) {
            if ($row['id'] == $id) {
                return $row;
            }
        }

        return null;
    }

    public static function updateInvoicesById(string $id, array $invoiceIds)
    {
        $data = self::read();

        foreach ($data as $i => $row) {
            if ($row['id'] == $id) {
                $data[$i]['invoices'] = $invoiceIds;

                break;
            }
        }

        self::write($data);
    }

    public static function appendInvoices(array $invoiceIds)
    {
        $data = self::read();

        $dt = date('d.m.Y H:i:s');

        $data[] = [
            'id' => md5($dt . implode(',', $invoiceIds)),
            'date' => $dt,
            'invoices' => $invoiceIds
        ];

        self::write($data);
    }

    public static function removeById(string $id)
    {
        $data = self::read();

        $newData = [];

        foreach ($data as $row) {
            if ($row['id'] != $id) {
                $newData[] = $row;
            }
        }

        if (count($data) == count($newData)) {
            throw new \Exception('Unable to find record by id: ' . $id);
        }

        self::write($newData);
    }

    public static function importFile(string $content)
    {
        $result = file_put_contents(self::FILENAME, $content);

        if ($result === false) {
            throw new \Exception('Cant save file');
        }
    }
}
