<?php

namespace App\Helpers;

class ExportHelper
{
    public static function exportToCsv(array $csvData, string $filename = null): string
    {
        if (empty($csvData)) {
            throw new \Exception('No data to export.');
        }

        $filename = $filename ?? 'export_' . now()->format('Ymd_His') . '.csv';
        $tempPath = storage_path('app/tmp/' . $filename);

        // Ensure tmp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0777, true);
        }

        $handle = fopen($tempPath, 'w+');
        fputcsv($handle, array_keys($csvData[0]));
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $tempPath;
    }
    public static function exportToMedia(array $csvData, $model, string $collection = 'exports', string $filename = null)
    {
        $filePath = self::exportToCsv($csvData, $filename);
        $media = $model->addMedia($filePath)
            ->usingName(basename($filePath))
            ->toMediaCollection($collection);
        // No need to unlink($filePath) as Spatie handles it
        return $media;
    }
} 