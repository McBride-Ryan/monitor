<?php

namespace App\Http\Controllers;

use App\Models\VendorSchemaMapping;
use App\Services\DataStandardizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorImportController extends Controller
{
    public function __construct(
        private DataStandardizationService $standardization,
    ) {}

    public function index(): Response
    {
        $mappings = VendorSchemaMapping::orderBy('vendor_name')->get();
        $vendors = $mappings->pluck('vendor_name')->unique()->values();

        return Inertia::render('VendorImport', [
            'mappings' => $mappings,
            'vendors' => $vendors,
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'vendor' => 'required|string',
        ]);

        $vendor = $request->input('vendor');
        $rows = $this->parseCsv($request->file('file'));

        $preview = [];
        foreach ($rows as $row) {
            $mapped = $this->standardization->mapVendorRow($vendor, $row);

            // Normalize known attribute columns
            foreach (['material', 'color', 'unit'] as $attrType) {
                if (isset($mapped[$attrType])) {
                    $mapped[$attrType] = $this->standardization->normalizeAttribute($attrType, $mapped[$attrType]);
                }
            }

            $brand = $mapped['brand'] ?? $vendor;
            $violations = $this->standardization->validateBrandCompliance($brand, $mapped);

            $preview[] = [
                'original' => $row,
                'mapped' => $mapped,
                'violations' => $violations,
            ];
        }

        return response()->json(['preview' => $preview]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'vendor' => 'required|string',
        ]);

        $vendor = $request->input('vendor');
        $rows = $this->parseCsv($request->file('file'));

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $mapped = $this->standardization->mapVendorRow($vendor, $row);

            foreach (['material', 'color', 'unit'] as $attrType) {
                if (isset($mapped[$attrType])) {
                    $mapped[$attrType] = $this->standardization->normalizeAttribute($attrType, $mapped[$attrType]);
                }
            }

            $brand = $mapped['brand'] ?? $vendor;
            $violations = $this->standardization->validateBrandCompliance($brand, $mapped);

            if (! empty($violations)) {
                $skipped++;
                $errors[] = ['row' => $index + 1, 'violations' => $violations];
            } else {
                $imported++;
            }
        }

        return response()->json([
            'imported' => $imported,
            'skipped' => $skipped,
            'total' => count($rows),
            'errors' => $errors,
        ]);
    }

    private function parseCsv(\Illuminate\Http\UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);

            return [];
        }

        $headers = array_map('trim', $headers);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }

        fclose($handle);

        return $rows;
    }
}
